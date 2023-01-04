# Last Modified: Fri Dec 18 15:02:16 2009
#include <tunables/global>
/1/local/R/2.9.0/bin/R {
  #include <abstractions/base>
  #include <abstractions/bash>
  #include <abstractions/nameservice>
  #include <abstractions/perl>
  #include <abstractions/workingwiki-util>

  /1/local/R/2.9.0/bin/R mr,
  /1/local/R/2.9.0/lib64/R/** mrw,
  /1/local/R/2.9.0/lib64/R/bin/R ixr,
  /1/local/R/2.9.0/lib64/R/bin/exec/R ixr,
  /1/local/R/2.9.0/lib64/R/bin/pager ixr,
  /bin/bash ix,
  /bin/rm ixr,
  /bin/uname ixr,
  /dev/tty rw,
  /tmp/ rw,
  /tmp/** rw,
  /usr/bin/less ixr,
  /usr/bin/perl5.10.0 ixr,
}
