#!/usr/bin/perl
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

my($WAITING,$BODYTAG,$INBODY,$PASTBODY) = (1 .. 4);
my($state) = $WAITING;
my($dbg) = 0;
my($text) = '';
while(<>)
{ # this is shameful - learn to use XSLT right!
  chomp;
  s/(<|<\/)m:/$1/g;
  s|<math\s|<math xmlns="http://www.w3.org/1998/Math/MathML" |ig;
  s/(<|<\/)svg:/$1/g;
  s|<svg\s|<svg xmlns="http://www.w3.org/2000/svg" |ig;
  if($state == $WAITING)
  { if (/<article\b/i)
    { $state = $BODYTAG;
      $dbg && print "\n=== saw article ===\n";
      $dbg && print "[$_ ==> ";
      s/^.*<article//i;
      $dbg && print "$_]\n";
    }
  }
  if ($state == $BODYTAG && />/)
  { $state = $INBODY;
    $dbg && print "\n=== saw article tag close ===\n";
    $dbg && print "[$_ ==> ";
    s/^[^>]*>//;
    $dbg && print "$_]\n";
    $text .= "<span class=\"latexml latexml-inline\">";
  }
  if ($state == $INBODY && /<\/article/i)
  { $state = $PASTBODY;
    $dbg && print "\n=== saw /article ===\n";
    $dbg && print "[$_ ==> ";
    s/<\/article.*$//;
    $dbg && print "$_]\n";
  }
  if (($state == $INBODY || $state == $PASTBODY) && /\S/)
  { $text .= $_; }
  if ($state == $PASTBODY)
  { last;
  }
}

#print "\n<br clear='all'/>\n";
$text .= "</span>";
my @pmatches = ($text =~ /ltx_p/);
my $npmatches = @pmatches; # http://stackoverflow.com/questions/7218238/regular-expression-match-count-in-perl
if ( $npmatches == 1 ) {
print "<!-- $npmatches matches -->";
  $text =~ s|^\s*(<span.*?>)\s*<div.*?>\s*<p.*?>\s*(.*)\s*</p>\s*</div>\s*(</span>)[\s\n]*$|$1$2$3|sm;
}
print $text;
exit;
