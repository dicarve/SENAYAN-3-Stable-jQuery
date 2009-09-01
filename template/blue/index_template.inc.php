<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="description" content="senayan : an open source for library automation" />
<meta name="keywords" content="senayan, library, automation, open source, book, collection" />
<meta name="author" content="eddy subratha" />
<meta name="copyright" content="senayan" />
<link rel="icon" href="webicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="webicon.ico" type="image/x-icon" />
<title><?php echo $page_title; ?></title>
<link href="template/core.style.css" rel="stylesheet" type="text/css" />
<link href="<?php echo $sysconf['template']['css']; ?>" rel="stylesheet" type="text/css" />
<!--[if gte IE 7]>
<link rel="stylesheet" media="screen" type="text/css" href="ie7.css" />
<![endif]-->
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/form.js"></script>
<script type="text/javascript" src="js/gui.js"></script>
<?php echo $metadata; ?>
</head>
<body>
    <div id="wrappper">
    <div id="container">
        <div id="header">
            <img src="template/blue/images/logo.png" border="0" alt="" />
            <div class="title green"><?php echo $sysconf['library_name']; ?><div class="title2"><?php echo $sysconf['library_subname']; ?></div></div>
            <ul id="nav">
                 <li><a class="menu" href="index.php"><?php echo lang_template_topmenu_1; ?></a></li>
                 <li><a class="menu" href="index.php?p=libinfo"><?php echo lang_template_topmenu_2; ?></a></li>
                 <li><a class="menu" href="index.php?p=help"><?php echo lang_template_topmenu_3; ?></a></li>
                 <li><a class="menu" href="index.php?p=login"><?php echo lang_template_topmenu_4; ?></a></li>
            </ul>
        </div>
        <div id="picture"><span>Library Picture</span></div>
        <div id="left">
        <?php echo $header_info; ?>
        <h1 class="title_bar"><?php echo $info; ?></h1>
        <?php echo $main_content; ?>
        </div>

        <div id="right">
        <!-- language selection -->
        <h1 class="title_bar"><?php echo lang_sys_common_language_select; ?></h1>
        <form name="langSelect" action="index.php" method="get">
        <select name="select_lang" style="width: 99%;" onchange="document.langSelect.submit();">
        <?php echo $language_select; ?>
        </select>
        </form>
        <br />
        <br />
        <!-- language selection end -->
        <h1 class="title_bar"><?php echo lang_template_simple_search; ?></h1>
        <form name="simpleSearch" action="index.php" method="get">
        <input type="text" name="keywords" class="search" /><br /><br />
        <input type="submit" name="search" value="<?php echo lang_sys_common_form_search; ?>" class="submit" />
        </form>
        <br />
        <br />
        <h1 class="title_bar"><?php echo lang_template_adv_search; ?></h1>
        <form name="advSearch" action="index.php" method="get">
        <?php echo lang_mod_biblio_field_title; ?> :<br />
        <input type="text" name="title" class="search" /><br /><br />
        <?php echo lang_mod_biblio_field_authors; ?> :<br />
        <?php echo $advsearch_author; ?><br /><br />
        <?php echo lang_mod_biblio_field_topic; ?> :<br />
        <?php echo $advsearch_topic; ?><br /><br />
        <?php echo lang_mod_biblio_field_isbn; ?> :<br />
        <input type="text" name="isbn" class="search" /><br />
        <?php echo lang_mod_biblio_field_gmd; ?> :<br />
        <select name="gmd" style="width: 99%;" class="marginTop" />
        <?php echo $gmd_list; ?>
        </select><br /><br />
        <?php echo lang_mod_biblio_item_field_ctype; ?> :<br />
        <select name="colltype" style="width: 99%;" class="marginTop" />
        <?php echo $colltype_list; ?>
        </select><br /><br />
        <?php echo lang_mod_biblio_item_field_location; ?> :<br />
        <select name="location" style="width: 99%;" class="marginTop" />
        <?php echo $location_list; ?>
        </select><br />
        <br />
        <input type="submit" name="search" value="<?php echo lang_sys_common_form_search; ?>" class="submit" />
        <!-- <input type="button" value="More Options" onclick="" class="button marginTop" /> -->
        </form>
        <br />
        <h1 class="title_bar">About</h1>
        <p>
        Senayan is an open source Library Management System. It is build on Open source technology like PHP and MySQL. Senayan provides many features such as Bibliography database, Circulation, Membership and many more that will help "automating"  library tasks. This project is proudly sponsored by Pusat Informasi dan Humas Depdiknas and licensed under GPL v3.
        </p>
        </div>

        <div class="fixedclear"></div>

        <div id="footer">
        <p>
        Senayan was produce by Pusat Informasi dan Humas Depdiknas<br />
        This Software is Released Under <a href="http://www.gnu.org/copyleft/gpl.html" title="GNU GPL License" target="_blank" class="link">GNU GPL License</a> Version 3<br />Valid <a href="http://validator.w3.org/check?uri=referer" class="link" target="_blank">XHTML</a> | <a href="#" class="link">CSS</a> | Design By <a href="http://eddy.ptpci.co.id" target="_blank" class="link">Eddy Subratha</a>, Ported By <a href="http://dicarve.blogspot.com" target="_blank" class="link">Arie Nugraha</a>
        </p>
        </div>
    </div>
    </div>
</body>
</html>
