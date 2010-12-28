#!/usr/bin/perl -w
# Multiplexed Socket Daemon for Flash/Silverlight plugins in Proxy Revealer MOD (phpbb)
# Author: jasmineaura < jasmine.aura@yahoo.com > (Jasmine Hasan)
# Licensed under http://opensource.org/licenses/gpl-license.php GNU Public License
# $Id: xmlsockd.pl 23 2008-09-16 13:13:01Z jasmine.aura@yahoo.com $

use strict;
use IO::Socket;
use IO::Select; 
use Fcntl;

use POSIX qw(setsid);
use POSIX qw(:stdio_h); # For BUFSIZ
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
					Listen		=> SOMAXCONN,
					ReuseAddr	=> 1);
$Server or dienice("Socket error: $!");

$log->warning("Listening on port $port");

# begin with empty buffers
my %inbuffer	= ();				# holds the incomplete command read from clients
my %outbuffer	= ();				# holds response data not yet sent

nonblock($Server);					# turn on non-blocking I/O for server socket
my $s = IO::Select->new($Server);	# Multiplexing Using select

# As long as the daemon is running, accept connections and check reads and writes
while ($running) {
	my ($Client, $iPort, $iAddr, $ipStr, $rv, $data);

	# anything to read or accept?
	foreach $Client ($s->can_read(1))
	{
		if ($Client == $Server) {
			$Client = $Server->accept;	# accept a new connection
			$s->add($Client);			# add client to the read/write queue
			nonblock($Client);			# turn on non-blocking I/O for client socket
		} else {
			$data = '';					# read data
			$rv = $Client->recv($data, POSIX::BUFSIZ, 0);

			unless (defined($rv) && length $data) {
				# This would be the end of file, so close the client and move on
				delete $inbuffer{$Client};
				delete $outbuffer{$Client};
				$s->remove($Client);
				close $Client;
				next;
			}

			$inbuffer{$Client} .= $data;	# concat to allow partial reads for slow clients

			# Test whether the data in the buffer or the data we just read means there is a complete request waiting
			# to be fulfilled (null character terminated).  If there is, set $outbuffer{$Client} to the response.
			if ($inbuffer{$Client} =~ m/(.*)\0/) {
				($iPort, $iAddr) = sockaddr_in(getpeername($Client));	# get client's in_addr
				$ipStr = inet_ntoa($iAddr);								# convert in_addr to IP string

				if ($inbuffer{$Client} =~ m|<policy-file-request/>|)
				{
					$outbuffer{$Client} = "$policy\0";
					$log->warning("XML Policy file request from: $ipStr") if ($log_connections);
				}
				elsif ($inbuffer{$Client} =~ m|getmyip|)
				{
					$outbuffer{$Client} = "$ipStr\0";
					$log->warning("XML IP request from: $ipStr") if ($log_connections);
				}
				else
				{
					$outbuffer{$Client} = "Invalid request\0";
					$log->warning("Ignoring invalid request from: $ipStr") if ($log_connections);
				}
				delete $inbuffer{$Client};
			}
		}
	}

	# Buffers to flush?
	foreach $Client ($s->can_write(1)) {
		# Skip this client if we have nothing to say
		next unless exists $outbuffer{$Client};

		$rv = $Client->send($outbuffer{$Client}, 0);
		unless (defined $rv) {
			$log->warning("I was told I could write, but I can't") if ($log_connections);
		}

		delete $outbuffer{$Client};
		$s->remove($Client);
		close($Client);
	}
}

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
# nonblock($socket) puts socket into nonblocking mode
###
sub nonblock {
	my $socket = shift;
	my $flags;

	$flags = fcntl($socket, F_GETFL, 0) or dienice "Can't get flags for socket: $!";
	fcntl($socket, F_SETFL, $flags | O_NONBLOCK) or dienice "Can't make socket nonblocking: $!";
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
