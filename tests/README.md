This folder contains two scripts that can be used to perform stress tests
on a few aspects of some bottleneck of the contest.

These tests are made to be used with [multimechanize-tool](http://testutils.org/multi-mechanize/), see [doc](http://testutils.org/multi-mechanize/setup.html) for
installation.

Once installation is complete, set up a new test suite, and put the two files
in the test_scripts directory of your test suite. You can then use them as
described in [the doc](http://testutils.org/multi-mechanize/configfile.html).

Then you have to tune some values, mostly the URLs and the password in 
`access_contest.py`.
