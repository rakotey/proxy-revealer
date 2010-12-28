#!/usr/bin/perl -w
# Basic Socket Daemon for Flash/Silverlight plugins in Proxy Revealer MOD (phpbb)
# Author: jasmineaura <jasmine.aura@yahoo.com> (Jasmine Hasan)
# Licensed under http://opensource.org/licenses/gpl-license.php GNU Public License
# $Id: xmlsockd-basic.pl 23 2008-09-16 13:13:01Z jasmine.aura@yahoo.com $

use strict;
use Socket;
use POSIX qw(setsid);
use Cwd qw(abs_path);

###
# Change default configuration here, if needed
###

# Our server port (Any number between 1024 and 65535)
my $port = 9999;

# socket-send-timeout and socket-receive-timeout (long , not floats; i.e. no fractions)
# ex: to set 1.5s timeout, set to 1 and 1000, respectively (timeout is the sum of both)
my $t_sec = 1;		# seconds (1 second = 1,000,000us)
my $t_usec = 0;		# microseconds (1000us = 0.5s)

# Do you want to see debug messages on STDOUT? 1 = yes, 0 = no
# You might want to set it to 0 if script runs periodically from cron
my $debug = 1;

# Want to see debug messages if script is executed when it's already running?
# You might want to set it to 0 if script runs periodically from cron
my $debug_is_running = 1;

# !!!!!!!!!!!!!!!!!!!!!!!!!!
# Do not change the following unless you know what you're doing!!!
# !!!!!!!!!!!!!!!!!!!!!!!!!!
my $policy = '<?xml version="1.0"?><cross-domain-policy><allow-access-from domain="*" to-ports="' . $port . '" /></cross-domain-policy>';

# Absolute path to this script (ex: /home/user/perl/script.pl)
my $SELF = abs_path($0);
#$SELF =~ m/(.*)/; $ENV{PATH} = $SELF = $1; # lame untaint

# Absolute path to PID FILE, chop off script's extension (if any), and apped ".pid"
my $PID_FILE = $SELF; $PID_FILE =~ s|\.[^.]*$||; $PID_FILE .= ".pid";

###
# Check if script is already running, else update $PID_FILE
###
if (-e $PID_FILE) {
	open(PID_FILE, "<", $PID_FILE) or die "Can't read from $PID_FILE: $!\n";
	my $OLD_PID = <PID_FILE>;
	close PID_FILE;
	chomp $OLD_PID;
	$OLD_PID =~ m/(\d+)/ or die "PIDFILE shall only contain digits! ($OLD_PID)\n";
	my $PID = $1; # Assign untainted data
	if( kill(0, $PID) ) {
		print STDOUT "\nScript already running (PID: $PID)\n" if ($debug && $debug_is_running);
		exit(0); # We exit without error in case we're running script periodically from cron
	} else {
		daemonize();
	}
} else {
	daemonize();
}

###
# Setup signal handlers to give us time to cleanup (and report if debug on) before shutting down
###
my $running = 1;
my $restart = 0;
$SIG{HUP}  = sub { print STDOUT "\nCaught SIGHUP:  restarting gracefully\n"; $running = 0; $restart = 1; };
$SIG{INT}  = sub { print STDOUT "\nCaught SIGINT:  exiting gracefully\n"; $running = 0; };
$SIG{QUIT} = sub { print STDOUT "\nCaught SIGQUIT:  exiting gracefully\n"; $running = 0; };
$SIG{TERM} = sub { print STDOUT "\nCaught SIGTERM:  exiting gracefully\n"; $running = 0; };
$SIG{PIPE} = sub { print STDOUT "\nCaught SIGPIPE (Ignoring):  $!\n"; };

###
# BEGIN LISTENING
###
socket(Server, PF_INET, SOCK_STREAM, getprotobyname('tcp')) or die "socket: $!";
setsockopt(Server, SOL_SOCKET, SO_REUSEADDR, 1) or die "setsockopt: $!";

# SO_SNDTIMEO (socket-send-timeout) and SO_RCVTIMEO (socket-receive-timeout)
# 'L!L!' instead of 'LL', for automatic portability to 64-bit platforms.
# See: http://perldesignpatterns.com/?SocketProgramming
setsockopt(Server, SOL_SOCKET, SO_SNDTIMEO, pack('L!L!', $t_sec, $t_usec)) or die "setsockopt: $!";
setsockopt(Server, SOL_SOCKET, SO_RCVTIMEO, pack('L!L!', $t_sec, $t_usec)) or die "setsockopt: $!";

bind(Server, sockaddr_in($port, INADDR_ANY)) or die "bind() error: $!";
listen(Server, SOMAXCONN) or die "listen() error: $!";

print STDOUT "\nListening on port $port\n" if ($debug);

###
# As long as the daemon is running, listen for and handle received connections
###
while ($running)
{
	my ($client, $iPort, $iAddr, $ipStr, $request);
	$client = accept(Client, Server) or next;
	local $/ = "\0";	# Set the input record separator to null char (flash sockets)

	if (defined ($request = <Client>)) {
		($iPort, $iAddr) = sockaddr_in($client);	# get client's in_addr
		$ipStr = inet_ntoa($iAddr);					# convert in_addr to IP string
		chomp $request;								# Trim the null-byte line-terminator

		if ($request eq '<policy-file-request/>') {
			print Client "$policy\0";
			print STDOUT "XML Policy file request from: $ipStr\n" if ($debug);
		}
		elsif ($request eq 'getmyip') {
			print Client "$ipStr\0";
			print STDOUT "XML IP request from: $ipStr\n" if ($debug);
		}
		else {
			print Client "Invalid request\0";
			print STDOUT "Ignoring invalid request from: $ipStr\n" if ($debug);
		}
	}
	close(Client);
}

###
# Cleanup for a clean exit
###
close(Server);
unlink($PID_FILE); # Delete our pid file before exiting
# Are we restarting or exiting ?
if ($restart) {
	exec($SELF) or die "Restart failed: $!\n";
} else {
	print STDOUT "\nProcess Ended!\n";
}

###
# daemonize: forks and saves our PID in $PID_FILE
###
sub daemonize {
	# fork a child process and have the parent process exit to disassociate the process from controlling terminal or login shell
	defined(my $pid = fork) or die "Can't fork: $!";
	exit if $pid;

	# setsid turns the process into a session and group leader to ensure our process doesn't have a controlling terminal
	POSIX::setsid or warn "Can't start a new session: $!";

	open(PID_FILE, ">", $PID_FILE) or die "Can't open $PID_FILE: $!\n";
	print PID_FILE "$$\n";
	print STDOUT "\nNew PID: $$\n" if $debug;
	close PID_FILE;

	unless ($debug)
	{
		# Close the three standard filehandles by reopening them to /dev/null:
		for my $handle (*STDIN, *STDOUT, *STDERR)
		{
			open($handle, "+<", "/dev/null") || die "Can't reopen $handle to /dev/null: $!";
		}
	}
}
