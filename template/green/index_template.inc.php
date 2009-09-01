<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr"><head><title><?php echo $page_title; ?></title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="icon" href="webicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="webicon.ico" type="image/x-icon" />
<link href="template/core.style.css" rel="stylesheet" type="text/css" />
<link href="<?php echo $sysconf['template']['css']; ?>" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/form.js"></script>
<script type="text/javascript" src="js/gui.js"></script>
<?php echo $metadata; ?>
</head>
<body>

<table id="main" cellpadding="0" cellspacing="0">
<!-- main menu -->
<tr>
<td id="mainMenu" colspan="2">
<ul id="menuList">
        <li><a class="menu" href="index.php"><?php echo lang_template_topmenu_1; ?></a></li>
        <li><a class="menu" href="index.php?p=libinfo"><?php echo lang_template_topmenu_2; ?></a></li>
        <li><a class="menu" href="index.php?p=help"><?php echo lang_template_topmenu_3; ?></a></li>
        <li><a class="menu" href="index.php?p=login"><?php echo lang_template_topmenu_4; ?></a></li>
</ul>
</td>
</tr>
<!-- main menu end -->

<!-- header -->
<tr>
        <td id="mainHeader" colspan="2"><div id="headerImage">&nbsp;</div>
            <div id="libraryName"><?php echo $sysconf['library_name']; ?>
                <div id="librarySubName"><?php echo $sysconf['library_subname']; ?></div>
            </div>
        </td>
</tr>
<!-- header end -->

<!--body-->
<tr>
<!-- sidepan -->
<td id="sidepan" valign="top">
    <!-- language selection -->
        <div class="heading"><?php echo lang_sys_common_language_select; ?></div>
        <form name="langSelect" action="index.php" method="get">
        <select name="select_lang" style="width: 99%;" onchange="document.langSelect.submit();">
        <?php echo $language_select; ?>
        </select>
        </form>
    <!-- language selection end -->

    <!-- simple search -->
        <div class="heading"><?php echo lang_template_simple_search; ?></div>
        <form name="simpleSearch" action="index.php" method="get">
        <input type="text" name="keywords" style="width: 99%;" /><br />
        <input type="submit" name="search" value="<?php echo lang_sys_common_form_search; ?>" class="button marginTop" />
        </form>
    <!-- simple search end -->

    <!-- advanced search -->
        <div class="heading"><?php echo lang_template_adv_search; ?></div>
        <form name="advSearchForm" id="advSearchForm" action="index.php" method="get">
        <?php echo lang_mod_biblio_field_title; ?> :
        <input type="text" name="title" class="ajaxInputField" /><br />
        <?php echo lang_mod_biblio_field_authors; ?> :
        <?php echo $advsearch_author; ?><br />
        <?php echo lang_mod_biblio_field_topic; ?> :
        <?php echo $advsearch_topic; ?><br />
        <?php echo lang_mod_biblio_field_isbn; ?> :
        <input type="text" name="isbn" class="ajaxInputField" /><br />
        <?php echo lang_mod_biblio_field_gmd; ?> :
        <select name="gmd" class="ajaxInputField" />
        <?php echo $gmd_list; ?>
        </select>
        <?php echo lang_mod_biblio_item_field_ctype; ?> :
        <select name="colltype" class="ajaxInputField" />
        <?php echo $colltype_list; ?>
        </select>
        <?php echo lang_mod_biblio_item_field_location; ?> :
        <select name="location" class="ajaxInputField" />
        <?php echo $location_list; ?>
        </select>
        <br />
        <input type="submit" name="search" value="<?php echo lang_sys_common_form_search; ?>" class="button marginTop" />
        <!-- <input type="button" value="More Options" onclick="" class="button marginTop" /> -->
        </form>
    <!-- advanced search end -->

    <!-- license -->
        <div class="heading">License</div>
        <p>
        This Software is Released Under <a href="http://www.gnu.org/copyleft/gpl.html" title="GNU GPL License" target="_blank">GNU GPL License</a>
        Version 3.
        </p>
        <!-- license end -->

    <!-- sponsored -->
        <div class="heading">Produce By</div>
        <p align="center">
        <img src="template/pih_logo.png" alt="Pusat Informasi dan Humas Depdiknas RI" />
        <br />
        Pusat Informasi dan Humas Depdiknas
        </p>
    <!-- sponsored end -->

    <!-- w3c validate -->
        <div class="heading">Validated</div>
        <p align="center">
        <a href="http://validator.w3.org/check?uri=referer"><img
            src="template/valid-xhtml10.png"
            alt="Valid XHTML 1.0 Transitional" border="0" /></a>
        <br />
        <img src="template/valid-css.png" alt="Valid CSS" />
        </p>
    <!-- w3c validate end -->
</td>
<!-- main menu end -->
<!-- main content -->
<td id="mainContent" valign="top">
<?php echo $header_info; ?>
<div id="infoBox"><?php echo $info; ?></div>
<?php echo $main_content; ?>
<br />
</td>
<!-- main content end -->
</tr>
<!--body end-->

</table>

</body>
</html>
