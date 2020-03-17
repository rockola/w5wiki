<?php
/*
 *  W5Wiki
 *
 *  https://github.com/rockola/w5wiki/
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
$parser = new MarkdownExtra;

include_once "config.php";

/**
 * @param url String containing a URL
 * @return TRUE if url is an external URL (has a scheme part),
 *     otherwise FALSE
 */
function w5is_external_url($url) {
  $scheme = parse_url($url, PHP_URL_SCHEME);
  return ($scheme != FALSE && $scheme != NULL);
}

/**
 * HTML link to a page.
 *
 * @param page Page URL.
 * @param anchor Anchor text for the link.
 * @param cssclass CSS class for the link
 * @return a HTML link to the page
 */
function w5link($page, $anchor, $cssclass="w5link") {
  echo "<a class=\"$cssclass\" href=\"$page\">$anchor</a>";
}

/**
 * Link to the Wiki home page.
 *
 * @param anchor Anchor text.
 * @return HTML link to home page.
 */
function w5home($anchor) {
  w5link(W5_HOME, $anchor);
}

/**
 * Filter page names.
 *
 * @param pagetitle Page title
 */
function w5pagename($pagetitle) {
  return str_replace(' ', '_', strtolower($pagetitle));
}

function w5pagelink($action, $page="") {
  $pageedit = "";
  if ($page != "") {
    $pageedit = "&page=" . $page;
  }
  return "index.php?do=" . w5pagename($action) . $pageedit;
}

function w5action($action, $page="") {
  w5link(w5pagelink($action, $page), $action);
}

function w5pageview($pagetitle) {
  return "index.php?do=view&page=" . strtolower($pagetitle);
}

function w5page($pagetitle) {
  w5link(w5pageview($pagetitle), $pagetitle);
}

/**
 * Wiki main menu.
 *
 * Outputs HTML for the menu wrapped in a DIV.
 */
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
  echo "&nbsp;";
  w5page("Help");
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

function w5pages($parent=W5_CONTENT, $titles=TRUE) {
  $directory = new RecursiveDirectoryIterator($parent);
  $filter = new RecursiveCallbackFilterIterator(
      $directory,
      function($current, $key, $iterator) {
        $filename = $current->getFilename();
        if ($filename[0] === '.' || substr($filename, -1) === '~') {
          return FALSE;
        }
        return TRUE;
      });
  $iterator = new RecursiveIteratorIterator($filter);
  $files = array();
  foreach ($iterator as $info) {
    $file = preg_replace('!^' . preg_quote(W5_CONTENT) . '!', '', $info->getPathname());
    if ($titles) {
      $file = ucfirst(str_replace('_', ' ', preg_replace('/\.md$/', '', strtolower($file))));
    }
    $files[] = $file;
  }
  return $files;
}

function w5parentpagemenu() {
  echo "<div class=\"w5parentmenu\"><label for=\"parent\">Parent page</label>";
  echo "<select id=\"parent\" name=\"parent\">";
  echo "<option value=\"\">---</option>";
  foreach (w5pages() as $page) {
    echo "<option value=\"$page\">$page</option>";
  }
  echo "</select></div>";
}

function w5editform($page="") {
  $pagecontent = "";
  $title = "New page";
  if ($page != "") {
    $pagecontent = w5filecontents($page);
    $title = "Edit page " . $page;
  }
  echo "<h2>$title</h2>";
  echo "<form>";
  w5text("pagetitle", "Page title", $page);
  w5parentpagemenu();
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

function w5filename($pagetitle, $dir="") {
  if ($dir === "") {
    $dir = W5_CONTENT;
  }
  return $dir . preg_replace("!^a-z_/!", "", str_replace(' ', '_', strtolower($pagetitle))) . ".md";
}

function w5filecontents($pagetitle) {
  return file_get_contents(w5filename($pagetitle));
}

function w5view($pagetitle) {
  global $parser;
  $page = w5filecontents($pagetitle);
  // $html = MarkdownExtra::defaultTransform(w5replace_tags($page));
  $html = $parser->transform(w5replace_tags($page));
  echo $html;
}

function w5trashfile($pagetitle) {
  return W5_TRASH . str_replace('/', '__', w5filename($pagetitle));
}

function w5delete($pagetitle) {
  mkdir(W5_TRASH, 0755);
  rename(w5filename($pagetitle), w5trashfile($pagetitle));
}

function w5softwarelink($anchor) {
  return "<a href=\"" . W5_SOFTWARE_URL . "\">$anchor</a>";
}

function w5poweredby($link="") {
  $ret = "Powered by ";
  if ($link != "") {
    $ret .= w5softwarelink(W5_SOFTWARE);
  } else {
    $ret .= W5_SOFTWARE;
  }
  $ret .= " v" . W5_VERSION . " " . W5_VERSION_DATE;
  return $ret;
}

/**
 * Links to children of this page.
 */
function w5childpages() {
  global $page, $do;
  if ($do === 'view' && is_dir(w5directory($page))) {
    $children = w5pages(w5directory($page));
    if (!empty($children)) {
      echo "<div id=\"w5childpages\">Child pages: ";
      foreach ($children as $child) {
        w5page($child);
      }
      echo "</div>\n";
    }
  }
}

/**
 * Outputs page footer.
 *
 * TODO: move footer contents to a Markdown file.
 */
function w5footer() {
  w5childpages();
  echo "<hr>";
  echo "<div class=\"w5poweredby\">" . w5poweredby(TRUE) . "</div>\n";
}

function w5iconlink($icon, $page) {
  w5link($page, "<i class=\"material-icons\">" . $icon . "</i> " . ucfirst($icon), "w5button");
}

/**
 * Outputs site index.
 *
 * All Wiki pages are output in an unordered list.
 *
 * TODO: Handle subpages better, use JavaScript for dynamic
 * opening/closing of subpage hierarchies
 */
function w5siteindex() {
  echo "<ul>";
  foreach (w5pages() as $page) {
    echo "<li>";
    w5page($page);
    echo "<span class=\"w5buttonspace\">&nbsp;</span>";
    w5iconlink("edit", w5pagelink("edit", $page));
    w5iconlink("delete", w5pagelink("delete", $page));
    echo "</li>\n";
  }
  echo "</ul>";
}

function w5directory($parent_page="") {
  if ($parent_page != "") {
    $parent_page = strtolower($parent_page) . "/";
  }
  return W5_CONTENT . $parent_page;
}

$do = '';
$page = '';

/**
 * Process request parameters.
 *
 * After processing, global variable 'do' contains the action string
 * which defaults to 'view'. Global variable 'page' contains the name
 * of the page to view/edit/etc.
 */
function w5process_request() {
  global $do, $page;
  if (isset($_REQUEST['do'])) {
    $do = $_REQUEST['do'];
  }

  if (isset($_REQUEST['page'])) {
    $page = $_REQUEST['page'];
  }

  if ($do == 'view') {
    $pagetitle = $page;
  }

  if (isset($_REQUEST['submit'])) {
    /* create new page or store an edited page */
    $pagetitle = $_REQUEST['pagetitle'];
    $directory = w5directory($_REQUEST['parent']);
    if (!is_dir($directory)) {
      mkdir($directory, 0755, TRUE);
    }
    $filename = w5filename($pagetitle, $directory);
    file_put_contents($filename, $_REQUEST['pagecontent']);
    $do = 'view';
    $page = $pagetitle;
  }
}

/**
 * Initial setup.
 *
 * Called at beginning of execution to set up internal variables and
 * process request parameters.
 */
function w5setup() {
  global $parser;
  $parser->url_filter_func =
      function ($url) {
        if (w5is_external_url($url)) {
          return $url;
        } else {
          return w5pageview($url);
        }
      };
  w5process_request();
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
<?php
  echo "    <!-- " . w5poweredby() . " -->\n";
  echo "    <!-- " . W5_SOFTWARE_URL . " -->\n";
  w5setup();
?>
    <meta charset="utf-8">
    <title><?php echo W5_SITE; ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
    <link rel="stylesheet" href="w5style.css">
    <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
    <script src="w5wiki.js"></script>
  </head>
  <body>
    <h1><?php
  w5home(W5_SITE);
  if ($page != '') {
    echo ' : ';
    echo $page;
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
  case "delete":
    w5delete($page);
  /* TODO: this assumes site index is the only place where we can delete */
    w5siteindex();
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
}
?>
    </div>
  </body>
</html>
