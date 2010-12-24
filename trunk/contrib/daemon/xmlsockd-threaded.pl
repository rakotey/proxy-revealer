#!/usr/bin/perl -w
# Threaded Socket Daemon for Flash/Silverlight plugins in Proxy Revealer MOD (phpbb)
# Author: jasmineaura < jasmine.aura@yahoo.com > (Jasmine Hasan)
# Licensed under http://opensource.org/licenses/gpl-license.php GNU Public License
# $Id: xmlsockd-threaded.pl 23 2008-09-16 13:13:01Z jasmine.aura@yahoo.com $

use strict;
use threads;
use threads::shared;
use IO::Socket;

use POSIX qw(setsid);
use Proc::PID::File;
use Log::Dispatch;
use Log::Dispatch::File;
use Date::Format qw(time2str);
use Cwd qw(abs_path);
use File::Basename;
use File::Spec;

sub dienice ($);

###
# Change default configuration here, if needed
###

# Our server port (Any number between 1024 and 65535)
my $port = 9999;

# socket-send-timeout and socket-receive-timeout (long , not floats; i.e. no fractions)
# ex: to set 1.5s timeout, set to 1 and 1000, respectively (timeout is the sum of both)
my $t_sec = 1;		# seconds (1 second = 1,000,000us)
my $t_usec = 0;		# microseconds (1000us = 0.5s)

# Number of listener threads to spawn
# (2 or 3 threads are sufficient to handle 100 concurrent connections since our duty cycle is a few milliseconds)
my $listeners = 3;
# Max clients per thread that can be put on hold (per-thread Listen backlog)
# Keep this value low (2 to 5) to avoid slow clients from holding up many others
my $max_clients = 3;

# Want to log connections? 1 = yes, 0 = no
my $log_connections = 1;

# Want to log if script is executed when it's already running?
# You might want to set it to 0 if script runs periodically from cron
my $log_is_running = 1;

# Do you want to see (perl) debug messages on STDOUT/STDERR? 1 = yes, 0 = no
# You might want to set it to 0 if script runs periodically from cron
my $debug = 1;

# !!!!!!!!!!!!!!!!!!!!!!!!!!
# Do not change the following unless you know what you're doing!!!
# !!!!!!!!!!!!!!!!!!!!!!!!!!
my $policy = '<?xml version="1.0"?><cross-domain-policy><allow-access-from domain="*" to-ports="' . $port . '" /></cross-domain-policy>';

# Absolute path to this script (ex: /home/user/perl/script.pl)
my $SELF = abs_path($0);

# Get script name without the last extension, and get the base directory path
my ($ME, $BASE_DIR, $suffix) = fileparse($SELF, qr/\.[^.]*$/);
my $LOG_FILE = "$ME.log";

###
# Setup a logging agent
###
my $log = new Log::Dispatch(
	callbacks => sub { my %h=@_; return Date::Format::time2str('%B %e %T', time)." $ME\[$$]: ".$h{message}."\n"; }
);
$log->add(Log::Dispatch::File->new(	name		=> 'file1',
									min_level	=> 'warning',
									mode		=> 'append',
									filename	=> File::Spec->catfile($BASE_DIR, $LOG_FILE),
									));

###
# Fork and background daemon process
###
daemonize();
$log->warning("Logging Started");

###
# Setup signal handlers to give us time to cleanup (and log) before shutting down
###
my $running = 1;
share($running);	# Share our main thread status with the spawned threads
my $restart = 0;
$SIG{HUP}  = sub { $log->warning("Caught SIGHUP:  restarting gracefully"); $running = 0; $restart = 1; };
$SIG{INT}  = sub { $log->warning("Caught SIGINT:  exiting gracefully"); $running = 0; };
$SIG{QUIT} = sub { $log->warning("Caught SIGQUIT:  exiting gracefully"); $running = 0; };
$SIG{TERM} = sub { $log->warning("Caught SIGTERM:  exiting gracefully"); $running = 0; };
$SIG{PIPE} = sub { $log->warning("Caught SIGPIPE (Ignoring):  $!"); };

###
# BEGIN LISTENING
###
my $Server = new IO::Socket::INET(
					LocalPort	=> $port,
					Proto		=> 'tcp',
					Listen		=> $max_clients,
					ReuseAddr	=> 1);
$Server or dienice("Socket error: $!");

# SO_SNDTIMEO (socket-send-timeout) and SO_RCVTIMEO (socket-receive-timeout)
# 'L!L!' instead of 'LL', for automatic portability to 64-bit platforms.
# See: http://perldesignpatterns.com/?SocketProgramming
$Server->sockopt(SO_SNDTIMEO, pack('L!L!', $t_sec, $t_usec)) or die "setsockopt: $!";
$Server->sockopt(SO_RCVTIMEO, pack('L!L!', $t_sec, $t_usec)) or die "setsockopt: $!";

$log->warning("Listening on port $port");

###
# Spawn our listener threads and detach them since we don't want return values and don't to wait for them to finish
# "detach" also allows automatic cleanup of the thread and recycles its memory
###
threads->create(\&handleConnection, $Server)->detach() for (1..$listeners);
sleep while ($running);

###
# Cleanup for a clean exit
###
close($Server);
# Are we restarting or exiting ?
if ($restart) {
	# Nuke old pidfile before exec to avoid race issues on restart (sub daemonize)
	my $pidinstance = Proc::PID::File->new(dir => "$BASE_DIR", name => "$ME");
	$pidinstance->DESTROY;
	exec($SELF) or dienice("Restart failed: $!");
} else {
	$log->warning("Logging Stopped");
}


###
# handleConnection: per-thread connection(s) handler
###
sub handleConnection {
	my $Server = shift;
    my $tid = threads->tid();
	$log->warning("Thread ($tid) started");

	# As long as the daemon (the main thread) is running, accept connections and check reads and writes
	while ($running) {
		my ($Client, $iPort, $iAddr, $ipStr, $request);

		# anything to read or accept?
		while ($Client = $Server->accept()) {
			return if $running == 0;
			local $/ = "\0";	# Set the input record separator to null char (flash sockets)

			if (defined ($request = <$Client>)) {
				($iPort, $iAddr) = sockaddr_in(getpeername($Client));	# get client's in_addr
				$ipStr = inet_ntoa($iAddr);								# convert in_addr to IP string
				chomp $request;											# Trim the null-byte line-terminator

				if ($request eq '<policy-file-request/>') {
					print $Client "$policy\0";
					$log->warning("($tid): XML Policy file request from: $ipStr") if ($log_connections);
				}
				elsif ($request eq '<request>getmyip</request>') {
					print $Client "<data><ip>$ipStr</ip></data>\0";
					$log->warning("($tid): XML IP request from: $ipStr") if ($log_connections);
				}
				else {
					print $Client "Invalid request\0";
					$log->warning("($tid): Ignoring invalid request from: $ipStr") if ($log_connections);
				}
			}
			close($Client);
		}
	}

	# Main thread is no longer running, exit this thread
	$log->warning("Thread ($tid) exited");
}

###
# daemonize: puts server process in background
###
sub daemonize {
	# fork a child process and have the parent process exit to disassociate the process from controlling terminal or login shell
	defined(my $pid = fork) or dienice("Can't fork: $!");
	exit if $pid;
	# setsid turns the process into a session and group leader to ensure our process doesn't have a controlling terminal
	POSIX::setsid() or $log->warning("Can't start a new session: $!");

	# Get a PID file - or exit without error in case we're running periodically from cron
	if (Proc::PID::File->running(dir => "$BASE_DIR", name => "$ME", verify => "1"))
	{
		$log->warning("Daemon Already Running!") if ($log_is_running);
		exit(0);
	}

	unless ($debug)
	{
		# Close the three standard filehandles by reopening them to /dev/null:
		for my $handle (*STDIN, *STDOUT, *STDERR)
		{
			open($handle, "+<", "/dev/null") || $log->warning("Can't reopen $handle to /dev/null: $!");
		}
	}
}

###
# dienice: die with grace :)
###
sub dienice ($) {
  my ($package, $filename, $line) = caller;
  # write die messages to the log before die'ing
  $log->critical("$_[0] at line $line in $filename");
  die $_[0];
}
