#!/usr/bin/perl -w
# XML Socket Daemon for Flash Addon to Proxy Revealer MOD for phpbb
# Author: jasmineaura < jasmine.aura@yahoo.com > (Jasmine Hasan)
# Released under GPL License v2

use strict;
use warnings;
use IO::Socket;
use IO::Select;
use threads;

use POSIX qw(setsid);
use Proc::PID::File;
use Log::Dispatch;
use Log::Dispatch::File;
use Date::Format;
use File::Spec;
use FindBin qw($Bin);

sub dienice ($);

###
### Change default configuration here if needed
###

# Our server port (Any number between 1024 and 65535)
my $port = 9999;
# Number of listener threads to spawn
# (2 or 3 threads are sufficient to handle 100 concurrent connections since our duty cycle is a few milliseconds)
my $listeners = 3;
# Want to log connections? 1 = yes, 0 = no
my $log_connections = 1;
# Want to log if script is executed when it's already running?
# (You might want to set it to 0 if you run periodically from cron - too redundant)
my $log_is_running = 1;

# !!!!!!!!!!!!!!!!!!!!!!!!!!
# !!!WARNING!!! Do not change the following unless you know what you're doing!!!
# !!!!!!!!!!!!!!!!!!!!!!!!!!
my $content = '<?xml version="1.0"?><cross-domain-policy><allow-access-from domain="*" to-ports="' . $port . '" /></cross-domain-policy>';
my $NULLBYTE = pack( 'c', 0 );

###
### Detect our base directory where we create the log and pid files
###
our $BASE_DIR = $Bin;
# Get script name, chop off any preceding path it was called with and chop off its extension (ex: 'some/dir/script.pl' becomes 'script')
our $ME = $0; $ME =~ s|.*/||; $ME =~ s|\..*||;
our $LOG_FILE = "$ME.log";

###
### Setup a logging agent
###
my $log = new Log::Dispatch(
	callbacks => sub { my %h=@_; return Date::Format::time2str('%B %e %T', time)." $ME\[$$]: ".$h{message}."\n"; }
);
$log->add( Log::Dispatch::File->new( name		=> 'file1',
									 min_level	=> 'warning',
									 mode		=> 'append',
									 filename	=> File::Spec->catfile($BASE_DIR, $LOG_FILE),
									)
);

###
### Fork and background daemon process
###
startDaemon();
$log->warning("Logging Started");

###
### Setup signal handlers to give us time to cleanup (and log) before shutting down
###
my $running = 1;
$SIG{HUP}  = sub { $log->warning("Caught SIGHUP:  exiting gracefully"); $running = 0; };
$SIG{INT}  = sub { $log->warning("Caught SIGINT:  exiting gracefully"); $running = 0; };
$SIG{QUIT} = sub { $log->warning("Caught SIGQUIT:  exiting gracefully"); $running = 0; };
$SIG{TERM} = sub { $log->warning("Caught SIGTERM:  exiting gracefully"); $running = 0; };
$SIG{PIPE} = sub { $log->warning("Caught SIGPIPE (Ignoring):  $!"); $running = 1; };

if ( $running == 1 )
{
	###
	### BEGIN LISTENING
	###
	my $main_sock = new IO::Socket::INET(
						LocalPort	=> $port,
						Proto		=> 'tcp',
						Listen		=> SOMAXCONN,
						ReuseAddr	=> 1);
	$main_sock or dienice("Socket error :$!");

	$log->warning("Listening on port $port");

	###
	### Spawn our listener threads and detach them since we don't want return values and don't to wait for them to finish
	### "detach" also allows automatic cleanup of the thread and recycles its memory
	###
	for (1..$listeners) {
		threads->create(\&handleConnection, $main_sock)->detach;
	}
	sleep while ($running);
}

###
### Mark a clean exit in the log
###
$log->warning("Logging Stopped");

###
### startDaemon
###
sub startDaemon {
	# fork a child process and have the parent process exit to disassociate the process from controlling terminal or login shell
	defined(my $pid = fork) or dienice("Can't fork: $!");
	exit if $pid;
	# setsid turns the process into a session and group leader to ensure our process doesn't have a controlling terminal
	POSIX::setsid() or dienice("Can't start a new session: $!");

	# Get a PID file - or exit without error in case we're running periodically from cron
#	if ( Proc::PID::File->running(dir => "$BASE_DIR", name => "$ME", verify => "1") )
#	{
#		$log->warning("Daemon Already Running!") if ($log_is_running);
#		exit(0);
#	}
}

###
### handleConnection
###
sub handleConnection {
	my $main_sock = shift;
    my $tid = threads->tid();
	$log->warning("Thread ($tid) started");

	# Multiplexing Using select (See: http://www.unix.com.ua/orelly/perl/advprog/ch12_03.htm)
	my $s = new IO::Select( $main_sock );
	my ($new_sock, $sock, $fno, $clientAddr, $clientPort, $clientIp, $clientIpStr, $request);
	my %clients = ();
	my @ready = ();
	while ( @ready = $s->can_read )
	{
		foreach $new_sock (@ready)
		{
			if ($new_sock == $main_sock)
			{
				($sock, $clientAddr) = $main_sock->accept;
				( $clientPort, $clientIp ) = sockaddr_in( $clientAddr );
				$clientIpStr = inet_ntoa( $clientIp );
				$fno = fileno($sock);
				$clients{ $fno } = $clientIpStr;
				$s->add($sock);
			} else {
				$fno = fileno($new_sock);
				$clientIpStr = $clients{ $fno };
				delete $clients{ $fno };
				local $/ = $NULLBYTE;
				if ( defined ( $request = <$new_sock> ) )
				{
					chomp $request;
					if ( $request eq '<policy-file-request/>' )
					{
						$log->warning("($tid) : XML Policy file request from: $clientIpStr") if ($log_connections);
						print $new_sock $content.$NULLBYTE;
					} elsif ( $request =~ /<request>getmyip<\/request>/ ) {
						$log->warning("($tid) : XML IP request from: $clientIpStr") if ($log_connections);
						print $new_sock "<data><ip>$clientIpStr</ip></data>".$NULLBYTE;
					} else {
						$log->warning("($tid) : Ignoring unrecognized request from: $clientIpStr") if ($log_connections);
					}
				}
				$s->remove($new_sock);
				close $new_sock;
			}
		}
	}
}

###
### dienice
###
sub dienice ($) {
  my ($package, $filename, $line) = caller;
  # write die messages to the log before die'ing
  $log->critical("$_[0] at line $line in $filename");
  die $_[0];
}
