/* ProjectEngine compute server
 * Copyright (C) 2010 Lee Worden <worden.lee@gmail.com>
 * http://lalashan.mcmaster.ca/theobio/projects/index.php/ProjectEngine
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

/*
 * pe-make.c
 *
 * This is a single-purpose utility used by ProjectEngine to
 * run its make processes in a securer setting.
 *
 * It is invoked as if it were make, and it will pass all its arguments
 * to make.  
 *
 * This program can be used to run make in a chroot jail.  It needs to have
 * setuid-root file permissions to do this, which is why it is a compiled
 * program and one that is very inflexible about what it does.
 * The path to be used as the fake root directory in chroot() must be
 * passed in the environment variable "chroot-directory".
 *
 * The environment variable "time-limit" can be used to
 * pass the number of seconds before terminating the make process.
 *
 * Environment variable "MAKE" if set specifies the make executable.
 *
 * In order to work properly, the chroot-make executable must be owned
 * by the root user and have setuid permission, i.e.
 *   sudo chown root chroot-make
 *   sudo chmod u+s  chroot-make
 *
 * Additionally, the chroot-directory tree must contain make
 * and everything else needed for the make process to succeed.
 */
#include <stdio.h>
#include <signal.h>
#include <unistd.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <sys/param.h>
#include <sys/resource.h>
#include <time.h>
#include <stdlib.h>
#include <errno.h>
#include <string.h>
// MacOS does not define HOST_NAME_MAX so fall back to the POSIX value.
// We are advised to use sysconf(_SC_HOST_NAME_MAX) but we use this value
// to size an automatic array and we can't portably use variables for that.
#if !defined(HOST_NAME_MAX)
#define HOST_NAME_MAX _POSIX_HOST_NAME_MAX
#endif  // HOST_NAME_MAX

static int time_limit;
static int child_pid;

void kill_children_signal_handler (int signum)
{ int sig_to_pass;
  switch (signum) {
  case SIGALRM:
    fprintf(stderr, 
        "Time limit reached (%d seconds) — terminating the make process\n", 
        time_limit);
    sig_to_pass = SIGTERM;
    // Is SIGTERM is better than SIGKILL? make propagates it to the 
    // child processes, I think, but we signal the children anyway...
    break;
  default:
    sig_to_pass = signum;
    fputs("Signal received — terminating the make process\n", stderr);
    break;
  }
  if ( kill(-child_pid,sig_to_pass) == -1 )
  { perror("Error attempting to kill children");
    alarm(time_limit);
  }
}

void print_command_line(char **args)
{ int column = 0;
  while (*args)
  { if (column + strlen(*args) > 90)
    { fputs(" \\\n    ",stdout); column = 4; }
    else if (column > 0)
    { putchar(' '); ++column; }
    fputs(*args,stdout);
    column += strlen(*args);
    ++args;
  }
  putchar('\n');
  fflush(stdout);
}

void report_time(void)
{ time_t time_secs;
  if (time(&time_secs) == -1)
    perror("Couldn't get the system time");
  else
  { const char *time_string = ctime(&time_secs);
    if (time_string == NULL)
      perror("Couldn't get local time");
    else
      fputs(time_string,stdout);
  }
}

int main(int argc, char **argv)
{ int ret_code;
  
  const char *chroot_directory = getenv("chroot-directory");
  if (chroot_directory == NULL)
    chroot_directory = "/";
  const char *tlim = getenv("time-limit");
  if (tlim == NULL)
    tlim = "0";
  // time_limit is static
  time_limit = atoi(tlim);
  //if (time_limit <= 0)
  //  time_limit = 0; // infinite time limit
  const char *make_executable = getenv("MAKE");
  if (make_executable == NULL)
    make_executable = "/usr/bin/make";

  // If requested, write the parent's (i.e. the pe-make process's) pid
  // to a status file.  This is not requested during normal WW operation,
  // but it is by the WW-Background extension.
  // Note that the status file could also be a lockfile - since it's an
  // advisory lock we can write to the file without owning the lock.
  // Whether it's a lockfile or not, we're assuming we're the only one
  // trying to write to it.
  const char *statusfilename = getenv("status-file");
  FILE *statusfile = NULL;
  if (statusfilename != NULL)
  { statusfile = fopen(statusfilename, "a");
    if (statusfile == NULL)
      perror( "Could not open status file" );
    if (fprintf(statusfile, "pid = %ld\n", (long)getpid()) <= 0)
      perror( "Could not write pid to status file" );
    if (fprintf(statusfile, "starttime = %lld\n", (long long)time(0)) <= 0)
      perror( "Could not write starttime to status file" );
    //const char *username = getenv("user-name");
    //if (username != NULL)
    //  if (fprintf(statusfile, "username = %s\n", username) <= 0)
    //    perror( "Could not write username to status file" );
    fflush(statusfile);
    //fclose(pidfile);
  }

  // set up signal handlers to kill children if this process dies
  // (do this before fork() to avoid race condition, and because
  // sigaction is cancelled by exec())
  struct sigaction new_action;
  new_action.sa_handler = kill_children_signal_handler;
  sigemptyset (&new_action.sa_mask);
  new_action.sa_flags = 0;
  // INT, i.e. Ctrl-C
  ret_code = sigaction(SIGINT, &new_action, NULL);
  if ( ret_code == -1 )
    perror( "Error return from sigaction()" );
  // ABRT, i.e. Ctrl-\
  ret_code = sigaction(SIGABRT, &new_action, NULL);
  if ( ret_code == -1 )
    perror( "Error return from sigaction()" );
  // TERM, just in case
  ret_code = sigaction(SIGTERM, &new_action, NULL);
  if ( ret_code == -1 )
    perror( "Error return from sigaction()" );

  // set resource limits to prevent various kinds of runaway
  // make jobs from bringing the server to its knees
  struct rlimit rl;
  if (getrlimit(RLIMIT_NPROC,&rl) != 0)
  { perror( "Error return from getrlimit(RLIMIT_NPROC)" );
    exit(-1);
  }
  if (rl.rlim_max == 0 || rl.rlim_max > 1000)
    rl.rlim_max = rl.rlim_cur = 1000;
  if (setrlimit(RLIMIT_NPROC,&rl) != 0)
  { perror( "Error return from setrlimit(RLIMIT_NPROC)" );
    exit(-1);
  }

  // Now fork, make the child process do the work
  if ( (child_pid = fork()) != 0 )
  { // parent process code here

    // wait for the child to finish, and set alarm to kill it if it
    // doesn't finish in time.
    // time_limit <= 0 signifies no time limit
    if ( time_limit > 0 )
    { // register signal handler for alarm
      ret_code = sigaction(SIGALRM, &new_action, NULL);
      if ( ret_code == -1 )
        perror( "Error return from sigaction()" );
      alarm(time_limit);
    }

    int child_status;
    ret_code = waitpid(child_pid,&child_status,0);
    int exitcode;
    if ( ret_code == -1 )
    { // it seems to return -1 when I kill the children even though
      // it doesn't seem like it should...
      //fputs("Error return from wait()\n",stderr);
      exitcode = -1;
    }
    else if (WIFEXITED(child_status))
      exitcode = WEXITSTATUS(child_status);
    else
      exitcode = -1;
    if (statusfile != NULL)
    { if (fprintf(statusfile, "succeeded = %d\n", (exitcode == 0 ? 1 : 0)) <= 0)
        perror( "Could not write exit status to status file" );
      if (fprintf(statusfile, "endtime = %lld\n", (long long)time(0)) <= 0)
        perror( "Could not write endtime to status file" );
      fflush(statusfile);
    }
    if (exitcode == 0)
      fputs("Make succeeded.\n", stderr);
    else
      fprintf(stderr, "Make failed with exit code %d.\n", exitcode);

    report_time();
    exit(exitcode);
  }
    
  // from here down is the child process:
  // execute the chroot and make.

  // first: put the child process into a new process group, along
  // with all the children of the make process it will become, so
  // the parent can kill them all if necessary.
  // this call makes the pgid equal to the child's pid
  ret_code = setpgid(0,0);
  if (ret_code != 0)
  { perror("setpgid(0,0) failed");
    exit(errno);
  }

  // print what time it is to stdout, which is into the log file.
  // no reason to do chroot before doing this.
  // additionally, report the hostname, if requested.
  const char *report_hostname = getenv("report-hostname");
  if (report_hostname != NULL)
  { char hostname[HOST_NAME_MAX+1];
    if (gethostname(hostname,HOST_NAME_MAX+1) != 0)
    { perror("gethostname");
      exit(errno);
    }
    hostname[HOST_NAME_MAX] = '\0';
    fputs(hostname,stdout);
    fputs(", ", stdout);
  }
  report_time();

  // if we don't actually want to chroot we invoke this program
  // with chroot_directory == "/"
  // so catch that case and don't do the chroot.
  if (strlen(chroot_directory) != 1 || chroot_directory[0] != '/')
  {
    // it's very important to chdir into the chroot space
    // if this isn't done it's potentially possible to break out of
    // the chroot
    ret_code = chdir(chroot_directory);
    if (ret_code != 0)
    { char msg_buf[FILENAME_MAX + 30];
      sprintf(msg_buf,"chdir(%s) failed", chroot_directory);
      perror(msg_buf);
      exit(errno);
    }

    // save the uid for later
    uid_t save_uid = getuid();

    // we have to become root in order to call chroot()
    ret_code = setuid(0);
    if (ret_code != 0)
    { perror("setuid(0) failed");
      exit(errno);
    }

    // call chroot()
    // this changes the interpretation of /
    ret_code = chroot(".");
    if (ret_code != 0)
    { perror("chroot(.) failed");
      exit(errno);
    }

    // now we drop back to the user id we were called with, so that
    // make doesn't get executed as root.
    // important to use setuid, not seteuid, because that would allow
    // a child process to switch back to running as root.
    ret_code = setuid(save_uid);
    if (ret_code != 0)
    { perror("setuid(save_uid) failed");
      exit(errno);
    }
  }

  /*
  ret_code = chdir(working_directory);
  if (ret_code != 0)
  { char msg_buf[FILENAME_MAX + 30];
    sprintf(msg_buf,"chdir(%s) failed", working_directory);
    perror(msg_buf);
    exit(errno);
  }

  ret_code = system("ls /");
  if (ret_code == -1)
  { perror("system(ls /) failed");
    exit(errno);
  }

  char cwdbuf[FILENAME_MAX];
  char *cwd = getcwd(cwdbuf, sizeof cwdbuf);
  if (cwd == NULL)
  { perror("Could not get cwd");
    exit(errno);
  }
  printf("cwd: %s\n",cwd);
  fflush(stdout);
  */

  // print the command line to the log file
  // print_command_line(argv);

  // assemble the make command line
  char *exec_args[argc+1];
  int i;
  exec_args[0] = (char *)make_executable;
  for (i = 1; i < argc; ++i)
  { exec_args[i] = argv[i];
  }
  exec_args[argc] = 0;

  // print out the make command line before doing it
  print_command_line(exec_args);

  // now do it
  // (note this is the /usr/bin/make or whatever within the chroot directory)
  ret_code = execv(make_executable,exec_args);
  // if we get to here it's an error
  perror("execv(make) failed");
  exit(errno);
}
