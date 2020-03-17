Install w5wiki
================

* _user_: web server user
* _group_: web server group

1. Go to the directory where you want to install w5wiki, this directory should be (under) the web server root
1. `git clone https://github.com/rockola/w5wiki.git`
1. `chown -R user:group w5wiki`
1. Check that the directory `w5wiki/content` is writable by _user_
1. If w5wiki is not directly under web root, edit the definition of `W5_HOME` in `w5wiki/config.php` accordingly
1. Point your web browser at `/w5wiki/` (if not directly under web root, modify accordingly)
