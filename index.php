<?php
/*
 *  W5
 *
 *  https://github.com/rockola/w5wiki
 *
 *  MIT License
 *
 *  Copyright (c) 2020 Ola Rinta-Koski
 *
 *  Permission is hereby granted, free of charge, to any person
 *  obtaining a copy of this software and associated documentation
 *  files (the "Software"), to deal in the Software without
 *  restriction, including without limitation the rights to use, copy,
 *  modify, merge, publish, distribute, sublicense, and/or sell copies
 *  of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be
 *  included in all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 *  EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 *  MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 *  NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 *  HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 *  WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 *  DEALINGS IN THE SOFTWARE.
 *
 */

require 'Michelf/MarkdownInterface.php';
require 'Michelf/Markdown.php';
require 'Michelf/MarkdownExtra.php';

use Michelf\MarkdownExtra;

include_once "config.php";

function w5link($page, $anchor, $cssclass="w5link") {
  echo "<a class=\"$cssclass\" href=\"$page\">$anchor</a>";
}

function w5home($anchor) {
  w5link(W5_HOME, $anchor);
}

function w5pagename($pagetitle) {
  return str_replace(' ', '_', strtolower($pagetitle));
}

function w5action($action, $page="") {
  $pageedit = "";
  if ($page != "") {
    $pageedit = "&page=" . $page;
  }
  w5link("index.php?do=" . w5pagename($action) . $pageedit, $action);
}

function w5page($pagetitle) {
  w5link("index.php?do=view&page=" . strtolower($pagetitle), $pagetitle);
}

function w5menu() {
  global $do, $page;
  echo "<div id=\"w5mainmenu\">";
  w5home("Home");
  w5action("New");
  w5page("About");
  w5action("Site Index");
  if ($do == 'view') {
    w5action("Edit", $page);
  }
  echo "</div>";
}

$textarea = 0;

function w5input($type, $divclass, $id, $title="", $content="") {
  global $textarea;
  echo "<div class=\"$divclass\" id=\"input_$id\" name=\"input_$id\">";
  if ($title != "") {
    echo "<label for=\"$id\">$title</label>";
  }
  if ($type == "textarea") {
    $textarea = $textarea + 1;
    echo "<textarea id=\"$id\" name=\"$id\"/>$content</textarea>";
  } else {
    echo "<input type=\"$type\" id=\"$id\" name=\"$id\"";
    if ($content != "") {
      echo " value=\"$content\"";
    }
    echo "/>";
  }
  echo "</div>\n";
}

function w5text($id, $title, $page="") {
  w5input("text", "inputtext inputtextfield", $id, $title, $page);
}

function w5textarea($id, $title, $content) {
  w5input("textarea", "inputtext inputtextarea", $id, $title, $content);
}

function w5editform($page="") {
  $pagecontent = "";
  if ($page != "") {
    $pagecontent = w5filecontents($page);
  }
  echo "<h2>New page</h2>";
  echo "<form>";
  w5text("pagetitle", "Page title", $page);
  w5textarea("pagecontent", "Content", $pagecontent);
  w5input("submit", "inputsubmit", "submit");
  echo "</form>";
}

function w5replace_tags($string) {
  foreach (W5_TAGS as $key => $value) {
    $string = str_replace('%' . $key . '%', $value, $string);
  }
  return $string;
}

function w5filename($pagetitle) {
  return W5_CONTENT . preg_replace("/[^a-z_]/", "", str_replace(' ', '_', strtolower($pagetitle))) . ".md";
}

function w5filecontents($pagetitle) {
  return file_get_contents(w5filename($pagetitle));
}

function w5view($pagetitle) {
  $page = w5filecontents($pagetitle);
  $html = MarkdownExtra::defaultTransform(w5replace_tags($page));
  echo $html;
}

function w5footer() {
  echo "<hr>";
}

function w5siteindex() {
  echo "<ul>";
  $pages = array_diff(scandir(W5_CONTENT), array('..', '.'));
  foreach ($pages as $page) {
    $title = ucfirst(str_replace('_', ' ', preg_replace('/\.md$/', '', strtolower($page))));
    echo "<li>";
    w5page($title);
    echo "</li>";
  }
  echo "</ul>";
}

$do = '';
if (isset($_REQUEST['do'])) {
  $do = $_REQUEST['do'];
}

$page = '';
if (isset($_REQUEST['page'])) {
  $page = $_REQUEST['page'];
}

$pagetitle = '';
if ($do == 'view') {
  $pagetitle = $page;
}

if (isset($_REQUEST['submit'])) {
  /* create new page */
  $pagetitle = $_REQUEST['pagetitle'];
  $filename = w5filename($pagetitle);
  file_put_contents($filename, $_REQUEST['pagecontent']);
  $do = 'view';
  $page = $pagetitle;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
<?php
  echo "    <!-- powered by " . W5_TITLE . " v" . W5_VERSION . " " . W5_VERSION_DATE . " -->\n";
  echo "    <!-- " . W5_SOFTWARE_URL . " -->\n";
?>
    <meta charset="utf-8">
    <title><?php echo W5_TITLE; ?></title>
    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
    <link rel="stylesheet" href="w5style.css">
    <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
    <script src="w5wiki.js"></script>
  </head>
  <body>
    <h1><?php
  w5home(W5_TITLE);
  if ($pagetitle != '') {
    echo ' : ';
    echo $pagetitle;
  }
?></h1>
<?php w5menu(); ?>
    <div id="w5content">
<?php
/*
 * Index page content
 */
switch ($do) {
  case "new":
    w5editform();
    break;
  case "view":
    w5view($page);
    break;
  case "edit":
    w5editform($page);
    break;
  case "site_index":
    w5siteindex();
    break;
}
?>
    </div>
    <div id="w5footer">
<?php
w5footer();
if ($textarea > 0) {
  echo "<script>";
  echo "var easyMDE = new EasyMDE();";
  echo "</script>";
} else {
  //
}
?>
    </div>
  </body>
</html>
