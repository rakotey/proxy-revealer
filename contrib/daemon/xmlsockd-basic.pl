#!/usr/bin/perl -w
# XML Socket Daemon for Flash Addon to Proxy Revealer MOD for phpbb
# Author: jasmineaura < jasmine.aura@yahoo.com > (Jasmine Hasan)
# Licensed under http://opensource.org/licenses/gpl-license.php GNU Public License
# $Id: xmlsockd-basic.pl 23 2008-09-16 13:13:01Z jasmine.aura@yahoo.com $

use strict;
use POSIX qw(setsid);
use Socket;
use FindBin qw($Bin);

my $port = 9999;		# Any number between 1024 and 65535
my $debug = 1;			# Do you want to see debug messages on STDOUT? 1 = yes, 0 = no

# Do not change the following unless you know what you're doing
my $content = '<?xml version="1.0"?><cross-domain-policy><allow-access-from domain="*" to-ports="' . $port . '" /></cross-domain-policy>';
my $NULLBYTE = pack( 'c', 0 );

###
### Detect our base directory where we create the pid files
###
our $BASE_DIR = $Bin;
# Get script name, chop off any preceding path it was called with and chop off its extension (ex: 'some/dir/script.pl' becomes 'script')
our $ME = $0; $ME =~ s|.*/||; $ME =~ s|\..*||;
our $PID_FILE = "$BASE_DIR/$ME.pid";

###
### Check if script is already running, else update $PID_FILE
###
if (-e $PID_FILE) {
	open(PID_FILE, "<", $PID_FILE) or die "Can't read from $PID_FILE: $!\n";
	my $OLD_PID = <PID_FILE>;
	close PID_FILE;
	chomp $OLD_PID;
	# Check that $OLD_PID only contains digits.
	die "PIDFILE shall only contain digits! ($OLD_PID)\n" if ($OLD_PID !~ /\d+/);
	# Check if script is still running
	if( kill(0, $OLD_PID) ) {
		print STDOUT "\nScript already running (PID: $OLD_PID)\n" if ($debug);
		exit(0); # We exit without error in case we're running script periodically from cron
	} else {
		daemonize();
	}
} else {
	daemonize();
}

###
### Setup signal handlers to give us time to cleanup (and log) before shutting down
###
my $running = 1;
$SIG{HUP}  = sub { print STDOUT "\nCaught SIGHUP:  exiting gracefully\n"; $running = 0; };
$SIG{INT}  = sub { print STDOUT "\nCaught SIGINT:  exiting gracefully\n"; $running = 0; };
$SIG{QUIT} = sub { print STDOUT "\nCaught SIGQUIT:  exiting gracefully\n"; $running = 0; };
$SIG{TERM} = sub { print STDOUT "\nCaught SIGTERM:  exiting gracefully\n"; $running = 0; };

###
### As long as the daemon is running, listen for and handle received connections
###
while ($running) {
	###
	### BEGIN LISTENING
	###
	socket( LISTENSOCK, PF_INET, SOCK_STREAM, getprotobyname( 'tcp' ) ) or die "socket() error: $!";
	setsockopt( LISTENSOCK, SOL_SOCKET, SO_REUSEADDR, pack( 'l', 1 ) ) or die "setsockopt() error: $!";
	bind( LISTENSOCK, sockaddr_in( $port, INADDR_ANY ) ) or die "bind() error: $!";
	listen( LISTENSOCK, SOMAXCONN ) or die "listen() error: $!";

	print STDOUT "\nListening on port $port\n" if ($debug);

	###
	### HANDLE CONNECTIONS
	###
	while ( my $clientAddr = accept( CONNSOCK, LISTENSOCK ) )
	{
		my ( $clientPort, $clientIp ) = sockaddr_in( $clientAddr );
		my $clientIpStr = inet_ntoa( $clientIp );
		local $/ = $NULLBYTE;

		my $request;
		if ( defined ( $request = <CONNSOCK> ) )
		{
			chomp $request;
			if ( $request eq '<policy-file-request/>' )
			{
				print STDOUT "XML Policy file request from: $clientIpStr\n" if ($debug);
				print CONNSOCK $content.$NULLBYTE;
			}
			elsif ( $request =~ /<request>getmyip<\/request>/ )
			{
				print STDOUT "XML IP request from: $clientIpStr\n" if ($debug);
				print CONNSOCK "<data><ip>$clientIpStr</ip></data>".$NULLBYTE;
			}
			else
			{
				print STDOUT "Ignoring unrecognized request from: $clientIpStr\n" if ($debug);
			}
		}
		else
		{
			print STDOUT "Ignoring NULL connection from: $clientIpStr\n" if ($debug);
		}
		close CONNSOCK;
	}
}

###
### Cleanup after clean exit
###
unlink($PID_FILE); # Delete our pid file before exiting
print STDOUT "\nProcess Ended!\n";


###
### daemonize: forks and saves our PID in $PID_FILE
###
sub daemonize {
	# fork a child process and have the parent process exit to disassociate the process from controlling terminal or login shell
	defined(my $pid = fork)   or die "Can't fork: $!";
	exit if $pid;
	# setsid turns the process into a session and group leader to ensure our process doesn't have a controlling terminal
	setsid or die "Can't start a new session: $!";
	open(PID_FILE, ">", $PID_FILE) or die "Can't open $PID_FILE: $!\n";
	print PID_FILE "$$\n";
	print STDOUT "\nNew PID: $$\n" if $debug;
	close PID_FILE;
}
