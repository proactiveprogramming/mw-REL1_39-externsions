# ProjectEngine compute server
# Copyright (C) 2010 Lee Worden <worden.lee@gmail.com>
# http://lalashan.mcmaster.ca/theobio/projects/index.php/ProjectEngine
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
# http://www.gnu.org/copyleft/gpl.html

# This is not the makefile used in projects managed by ProjectEngine,
# it's the one for setting up PE on the web server.

# before you can use PE you have to make the pe-make executable.
# PE uses this to run the regular make and kill it after the time limit.

default: pe-make

# the username for the web server is different on different systems
WWW_USER = $(shell egrep '(apache|www-data)' /etc/passwd | sed s/:.*//)

# this 'install' step is not needed currently
# it would be if we were using chroot
install-pe-make: pe-make
	chown $(WWW_USER) $<

# setuid permissions, needed for chroot
#  chmod 4550 $<

pe-make : pe-make.c
	$(CC) $(CFLAGS) $< -o $@
