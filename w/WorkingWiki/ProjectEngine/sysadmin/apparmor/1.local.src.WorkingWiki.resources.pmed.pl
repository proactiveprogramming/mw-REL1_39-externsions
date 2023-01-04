# Last Modified: Mon Nov 30 21:08:51 2009
#include <tunables/global>
/1/local/src/WorkingWiki/resources/pmed.pl {
  #include <abstractions/base>
  #include <abstractions/bash>
  #include <abstractions/nameservice>
  #include <abstractions/perl>
  #include <abstractions/workingwiki-util>

  /1/local/src/WorkingWiki/resources/pmed.pl mr,
  /bin/bash ixr,
  /dev/tty rw,
  /etc/wgetrc r,
  /proc/meminfo r,
  /proc/sys/kernel/ngroups_max r,
  /usr/bin/perl5.10.0 ixr,
  /usr/bin/wget ixr,
}
