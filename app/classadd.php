<?php
$_user->login();
if(!$_user->isAdmin(9)) {
	header('Location: /');
	exit;
}

/*	this is the script that takes new classified entries		**
**	without the email verification step				*/

require_once('../common/input.php');

// since we don't have an autoloader, we will "require in" the repositories and models
require_once('Models/ClassifiedAd.php');
require_once('Repositories/ClassifiedAdRepository.php');
require_once('Repositories/ClassifiedGroupRepository.php');
require_once('Repositories/ClassifiedCategoryRepository.php');

// now instantiate the repositories
$classifiedAdRepository = new \App\Repositories\ClassifiedAdRepository(new \App\Models\ClassifiedAd());
$classifiedGroupRepository = new \App\Repositories\ClassifiedGroupRepository();
$classifiedCategoryRepository = new \App\Repositories\ClassifiedCategoryRepository();

function IsValidEmail($EmailIn)
{
	if (!preg_match("/^([\w|\.|\-|_]+)@([\w||\-|_]+)\.([\w|\.|\-|_]+)$/i", $EmailIn))
	{
		return false;
		exit;
	}
	return true;
}

function IsInvalidPhone($Phone) {
	return (IsInvalidString($Phone, MIN_PHONE_LEN, MAX_PHONE_LEN));
}

function StripPhoneNumber($Phone) {
	return eregi_replace("[^0-9]", "", $Phone);
}

function DisplayPhoneNumber($Phone) {
	if (strlen($Phone) == 10)
		return "(".substr($Phone,0,3).") ".substr($Phone,3,3)."-".substr($Phone,6,4);
	else
		return "";
}

function SelectedIfEqual($X, $Y) {
	if ($X == $Y) echo "selected";
}

function CheckedIfEqual($X, $Y) {
   if ($X == $Y) echo "checked";
}

function IsInvalidString($String, $MinLen, $MaxLen) {
	if (strlen($String) < $MinLen) return true;
	if (strlen($String) > $MaxLen) return true;
	return false;
}

function IsInvalidState($State) {
	if ($State == "AL") return false;
	if ($State == "AK") return false;
	if ($State == "AZ") return false;
	if ($State == "AR") return false;
	if ($State == "CA") return false;
	if ($State == "CO") return false;
	if ($State == "CT") return false;
	if ($State == "DE") return false;
	if ($State == "DC") return false;
	if ($State == "FL") return false;
	if ($State == "GA") return false;
	if ($State == "HI") return false;
	if ($State == "ID") return false;
	if ($State == "IL") return false;
	if ($State == "IN") return false;
	if ($State == "IA") return false;
	if ($State == "KS") return false;
	if ($State == "KY") return false;
	if ($State == "LA") return false;
	if ($State == "ME") return false;
	if ($State == "MD") return false;
	if ($State == "MA") return false;
	if ($State == "MI") return false;
	if ($State == "MN") return false;
	if ($State == "MS") return false;
	if ($State == "MO") return false;
	if ($State == "MT") return false;
	if ($State == "NE") return false;
	if ($State == "NV") return false;
	if ($State == "NH") return false;
	if ($State == "NJ") return false;
	if ($State == "NM") return false;
	if ($State == "NY") return false;
	if ($State == "NC") return false;
	if ($State == "ND") return false;
	if ($State == "OH") return false;
	if ($State == "OK") return false;
	if ($State == "OR") return false;
	if ($State == "PA") return false;
	if ($State == "RI") return false;
	if ($State == "SC") return false;
	if ($State == "SD") return false;
	if ($State == "TN") return false;
	if ($State == "TX") return false;
	if ($State == "UT") return false;
	if ($State == "VT") return false;
	if ($State == "VA") return false;
	if ($State == "WA") return false;
	if ($State == "DC") return false;
	if ($State == "WV") return false;
	if ($State == "WI") return false;
	if ($State == "WY") return false;

	return true;
}

// ---------------------------------------------------------------------------
// Main script:
// ---------------------------------------------------------------------------

//field length requirements
define("MIN_CITY_LEN", 2);
define("MAX_CITY_LEN", 26);
define("MIN_TITLE_LEN", 1);
define("MAX_TITLE_LEN", 50);
define("MIN_BODY_LEN", 1);
define("MAX_BODY_LEN", 300);
define("MIN_PHONE_LEN", 10);
define("MAX_PHONE_LEN", 10);
define("MIN_PASSWORD_LEN", 6);
define("MAX_PASSWORD_LEN", 16);

//classified status code bits
define("AD_SUBMITTED", 1);
define("AD_APPROVED", 2);
define("AD_EXPIRED", 3);
define("AD_BLOCKED", 4);

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
	$editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

$ShowForm = true;
$message = "";

//insert this classified ad
if ((isset($_POST["insert"])) && ($_POST["insert"] == "insert")) {
	$CategoryID	= StringFromPost("cbcategory");
	$City		= StringFromPost("city");
	$State		= StringFromPost("cbState");
	$Title		= StringFromPost("title");
	$Body		= StringFromPost("body");
	$Email		= StringFromPost("email");
	$Password1	= StringFromPost("password1");
	$Password2	= StringFromPost("password2");
	$TermsAccepted	= StringFromPost("ckterms");
	$Phone		= StripPhoneNumber(StringFromPost("phone"));
	$Ip		= $_SERVER["REMOTE_ADDR"];
	$Status		= AD_APPROVED;
	$ActivationCode	= "";

	if (IsInvalidString($City, MIN_CITY_LEN, MAX_CITY_LEN)) {
		$message .= "'City' must be between ".MIN_CITY_LEN." and ";
		$message .= MAX_CITY_LEN." characters long.";
		$message .= "<br>Please enter a valid city name.<br><br>";

		//$City = "";
	}
	elseif (IsInvalidState($State)) {
		$message .= "Please enter a valid state.<br><br>";

		$State = "";
	}
	elseif (IsInvalidString($Title, MIN_TITLE_LEN, MAX_TITLE_LEN)) {
		$message .= "'Title' must be between ".MIN_TITLE_LEN." and ";
		$message .= MAX_TITLE_LEN." characters long.";
		$message .= "Please enter a valid title.<br><br>";

		//$Title = "";
	}
	elseif (IsInvalidString($Body, MIN_BODY_LEN, MAX_BODY_LEN)) {
		$message .= "'Classified Text' must be between ".MIN_BODY_LEN." and ";
		$message .= MAX_BODY_LEN." characters long.";
		$message .= "Please enter valid classified text.<br><br>";

		//$Body = "";
	}
	elseif (!IsValidEmail($Email)) {
		$message .= "'Email' appears to be incorrect.";
		$message .= "Please enter a valid email address.<br><br>";

		//$Email = "";
	}
	elseif (IsInvalidString($Password1, MIN_PASSWORD_LEN, MAX_PASSWORD_LEN)) {
		$message .= "'Password' must be between ".MIN_PASSWORD_LEN." and ";
		$message .= MAX_PASSWORD_LEN." characters long.";
		$message .= "Please enter a valid password.<br><br>";

		$Password1 = "";
		$Password2 = "";
	}
	elseif ($Password1<>$Password2) {
		$message .= "Passwords do not match.";
		$message .= "Please reenter a password into both password boxes.<br><br>";

		$Password1 = "";
		$Password2 = "";
	}
	elseif (IsInvalidPhone($Phone)) {
		$message .= "Please enter a valid phone number.<br><br>";

		//$Phone = "";
	}
	elseif ($TermsAccepted != "on") {
		$message .= "You must agree to the <a href=\"/index.php?content=serviceterms\" target=\"_new\">terms of use</a>.<br><br>";
	}
	else {
		//create an activation code (using the random password function
		$ActivationCode = $Password1;

		$result = $classifiedAdRepository->findActiveAds($Email);
		// TODO: this line allows anyone to enter as many classifieds as they want!!!
        $result = [];

		if (!empty($result)) {
			$message .= "There is a classified ad already posted for this email account.";

		} else {

		    try {
                // clean off old ads
                $classifiedAdRepository->deleteOldAds($Email);

                // insert the new ad
                $ad = $classifiedAdRepository->getNew();
                $ad->f_ccID = $CategoryID;
                $ad->caTitle = $Title;
                $ad->caBody = $Body;
                $ad->caCity = $City;
                $ad->caState = $State;
                $ad->caEmail = $Email;
                $ad->caPassword = md5($Password1);
                $ad->caPhone = $Phone;
                $ad->caDateCreated = date('Y-m-d H:i:s');
                $ad->caDateExpires = date('Y-m-d H:i:s'); // todo: add 14 days
                $ad->caActivationCode = md5($ActivationCode);
                $ad->caStatus = $Status;
                $ad->caIP = $Ip;
                $ad->save();

                $message = '"'.$Title.'" ad created.';
                $Title		= "";
                $Body		= "";
                $Phone		= "";

            } catch (PDOException $e) {
                $message = "There was an unexpected error procesing your request. ";
                $message .= "Please try again. Please contact customer service if the error persists.";
            }
		}
	}

} else {
	//first time to this page... insert post hidden field not detected, so show blank fields
	$message = "";

	$CategoryID	= "";
	$City		= "";
	$State		= "";
	$Title		= "";
	$Body		= "";
	$Email		= "";
	$Password1	= "";
	$Password2	= "";
	$Phone		= "";
}

//retrieve all of the classified ad groups/sections
$rsGroups = $classifiedGroupRepository->getAll();

//retrieve all of the classified ad categories for the selected group
$SelectedGroup ="1";
if (isset($_GET['cat'])) {
	$SelectedGroup = (get_magic_quotes_gpc()) ? $_GET['cat'] : addslashes($_GET['cat']);
}

//strip off the leftmost character since we only have groups of 1 to 9.  We will have to change this when we add a new group
//$SelectedGroup = substr($SelectedGroup,0,1);

$rsCategories = $classifiedCategoryRepository->findByGroup($SelectedGroup);
if (!$rsCategories) {
	$message = "<p><span class='ErrorText'>There was an unexpected error attempting to retrieve the categories for this classified section. ";
	$message .= "Please try again. Please contact customer service if the error persists.";
	$ShowForm = false;

} else {
	if ($rsCategories->count() === 0) {
		$message = "<p><span class='ErrorText'>There was an unexpected error attempting to retrieve the categories for this classified section. ";
		$message .= "Please try again. Please contact customer service if the error persists.";
		$ShowForm = false;
	}
}
require_once("../class/c_user.php");
require_once("../common/head.php");
?>
<script type="text/javascript" language="JavaScript">
function inputCount(field, countfield, maxlength)
{
 if (field.value.length > maxlength)
	field.value = field.value.substring(0, maxlength);
 else
	countfield.value = maxlength - field.value.length;
}
</script>
<center>
<table cellpadding=0 cellspacing=0 border=0 width=750>
<tr><td colspan=5>
<!-- include header -->
<?php require_once("../common/header.php");
$Tab = CLASSIFIED_TAB;
?>
</td></tr>
    <!-- Just to point out how old this code is, note all of the 11t.gif references? This is
         a 1 pixel by 1 pixel transparent GIF, specified with different heights or widths.
         This was an old way of controlling screen position and table cell/row sizes before
         absolute and relative positioning was supported in browsers. Back around 2004, maybe. -->
<tr><td colspan=5 height=5><img
  src="/images/11t.gif" height=5 width=1 alt=""></td></tr>

<tr><td colspan=5 style="border-style: solid; border-width: thin; border-color: #ffdf00; background: #222299;">
<!-- process feedback -->
<table cellpadding=0 cellspacing=0 border=0 width="100%" background="/images/sb.gif"><tr>
  <td rowspan=8 width=50 class=searchform><img src="./images/11t.gif" height=1 width=50 alt=""></td>
  <td colspan=3 height=5 class=searchform><img src="./images/11t.gif" height=5 width=1 alt=""></td>
  <td rowspan=7 width=50 class=searchform><img src="./images/11t.gif" height=1 width=50 alt=""></td>
</tr><tr>
  <td class=searchform align=center><span class=searchformbold>
    CREATING</span> a new CLASSIFIED AD!</td>
</tr><tr>
  <td colspan=3 height=5 class=searchform><img src="./images/11t.gif" height=5 width=1 alt=""></td>
</tr>
</table>

<tr><td colspan=5 height=5><img
  src="/images/11t.gif" height=5 width=1 alt=""></td></tr>

<tr><td valign=top width=180>
<!-- include site controls -->
<?php require_once("../common/leftnav.php"); ?>
  </td><td width=3><img
    src="/images/11t.gif" height=1 width=3 alt="">
  </td><td valign=top width=570>

  <!-- main content -->
    <table width="100%" class="maincenterpane" cellspacing=0 cellpadding=0 border=0><tr>
      <td colspan=3 height=5 class=formlabel><img
        src="/images/11t.gif" height=5 width=1 alt=""></td>

    </tr><tr>
      <td rowspan=10 width=5 class=formlabel><img
        src="/images/11t.gif" height=1 width=5 alt=""></td>
      <td class=formtext><span class=formlabel>Post a free classified ad on MightyPages!</span>
      </td><td rowspan=10 width=5 class=formtext><img
        src="/images/11t.gif" height=1 width=5 alt=""></td>

    </tr><tr><td height=10 class=formtext><img
      src="/images/11t.gif" height=10 width=1 alt=""></td>
    </tr>
<?php

      // $message = 'This is a test error message.  Something is terribly wrong!';
      if (strlen($message) > 0) { echo("<tr><td class=formerrortext>$message</td></tr>"); }

?>
    <tr><td height=10 class=formtext><img
      src="/images/11t.gif" height=10 width=1 alt=""></td>
    </tr>


    <tr><td class=formtext>

<p>Simply fill out this form to submit your free classified ad.
<?php if ($ShowForm == false) exit(); ?>

      <form action="<?php ($_SERVER['PHP_SELF']); ?>" method="get" name="cg" id="cg">
      <table width="100%" cellspacing=0 cellpadding=0 border=0>
      <tr>
        <td class=formlabel>Category:</td>
        <td class=formtext colspan=2><select name="cat" id="cat" onchange="javascript:cg.submit()">
<?php
    // We will anticipate that the object returned by the repository getAll method will be iterable
    // so we can use it directly in our foreach loop.
	foreach ($rsGroups as $row_rsGroups) {
     		echo('<option value="'.$row_rsGroups['cgID'].'" ');
     		SelectedIfEqual($SelectedGroup, $row_rsGroups['cgID']);
     		echo('>'.htmlspecialchars($row_rsGroups['cgName']).'</option>');
	};
?>
	</td>
      </tr>
      </form>
      <form action="<?php echo $editFormAction; ?>" method="post" name="fm" id="fm">
      <input type="hidden" name="submit" value="submit" />
      <tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel>Sub-Category:</td>
        <td class=formtext colspan=2><select name="cbcategory" id="cbcategory">
<?php
	foreach ($rsCategories as $row_rsCategories) {
     		echo('<option value="'.$row_rsCategories['ccID'].'" ');
     		SelectedIfEqual($CategoryID, $row_rsCategories['ccID']);
     		echo('>'.htmlspecialchars($row_rsCategories['ccName']).'</option>');
	}
?>
   </select></td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel>Title:</td>
        <td class=formtext><input name="title" type="text" id="title"
        	value="<?php echo htmlspecialchars($Title) ?>" size="30" maxlength="50" /></td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel>Text:</td>
        <td class=formtext><textarea name="body" cols="50" rows="6" id="body"
        	onKeyUp="inputCount(this.form.body,this.form.remChars,<?php echo(MAX_BODY_LEN); ?>);"
        	><?php echo htmlspecialchars($Body) ?></textarea></td>
      </tr><tr>
        <td class=formlabel></td>
        <td class=formtext><input readonly type="text" name="remChars" size="3" maxlength="3"
        	value="<?php echo(MAX_BODY_LEN); ?>"> characters remaining</td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel>City:</td>
        <td class=formtext><input name="city" type="text" id="city" value="<?php echo htmlspecialchars($City) ?>" size="30" maxlength="26" /></td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel>State:</td>
        <td class=formtext><select name="cbState" id="Select1">
     <option <?php SelectedIfEqual($State, "") ?> value="">Choose a State</option>
     <option <?php SelectedIfEqual($State, "AL") ?> value="AL">Alabama</option>
     <option <?php SelectedIfEqual($State, "AK") ?> value="AK">Alaska</option>
     <option <?php SelectedIfEqual($State, "AZ") ?> value="AZ">Arizona</option>
     <option <?php SelectedIfEqual($State, "AR") ?> value="AR">Arkansas</option>
     <option <?php SelectedIfEqual($State, "CA") ?> value="CA">California</option>
     <option <?php SelectedIfEqual($State, "CO") ?> value="CO">Colorado</option>
     <option <?php SelectedIfEqual($State, "CT") ?> value="CT">Connecticut</option>
     <option <?php SelectedIfEqual($State, "DE") ?> value="DE">Delaware</option>
     <option <?php SelectedIfEqual($State, "DC") ?> value="DC">D.C.</option>
     <option <?php SelectedIfEqual($State, "FL") ?> value="FL">Florida</option>
     <option <?php SelectedIfEqual($State, "GA") ?> value="GA">Georgia</option>
     <option <?php SelectedIfEqual($State, "HI") ?> value="HI">Hawaii</option>
     <option <?php SelectedIfEqual($State, "ID") ?> value="ID">Idaho</option>
     <option <?php SelectedIfEqual($State, "IL") ?> value="IL">Illinois</option>
     <option <?php SelectedIfEqual($State, "IN") ?> value="IN">Indiana</option>
     <option <?php SelectedIfEqual($State, "IA") ?> value="IA">Iowa</option>
     <option <?php SelectedIfEqual($State, "KS") ?> value="KS">Kansas</option>
     <option <?php SelectedIfEqual($State, "KY") ?> value="KY">Kentucky</option>
     <option <?php SelectedIfEqual($State, "LA") ?> value="LA">Louisiana</option>
     <option <?php SelectedIfEqual($State, "ME") ?> value="ME">Maine</option>
     <option <?php SelectedIfEqual($State, "MD") ?> value="MD">Maryland</option>
     <option <?php SelectedIfEqual($State, "MA") ?> value="MA">Massachusetts</option>
     <option <?php SelectedIfEqual($State, "MI") ?> value="MI">Michigan</option>
     <option <?php SelectedIfEqual($State, "MN") ?> value="MN">Minnesota</option>
     <option <?php SelectedIfEqual($State, "MS") ?> value="MS">Mississippi</option>
     <option <?php SelectedIfEqual($State, "MO") ?> value="MO">Missouri</option>
     <option <?php SelectedIfEqual($State, "MT") ?> value="MT">Montana</option>
     <option <?php SelectedIfEqual($State, "NE") ?> value="NE">Nebraska</option>
     <option <?php SelectedIfEqual($State, "NV") ?> value="NV">Nevada</option>
     <option <?php SelectedIfEqual($State, "NH") ?> value="NH">New Hampshire</option>
     <option <?php SelectedIfEqual($State, "NJ") ?> value="NJ">New Jersey</option>
     <option <?php SelectedIfEqual($State, "NM") ?> value="NM">New Mexico</option>
     <option <?php SelectedIfEqual($State, "NY") ?> value="NY">New York</option>
     <option <?php SelectedIfEqual($State, "NC") ?> value="NC">North Carolina</option>
     <option <?php SelectedIfEqual($State, "ND") ?> value="ND">North Dakota</option>
     <option <?php SelectedIfEqual($State, "OH") ?> value="OH">Ohio</option>
     <option <?php SelectedIfEqual($State, "OK") ?> value="OK">Oklahoma</option>
     <option <?php SelectedIfEqual($State, "OR") ?> value="OR">Oregon</option>
     <option <?php SelectedIfEqual($State, "PA") ?> value="PA">Pennsylvania</option>
     <option <?php SelectedIfEqual($State, "RI") ?> value="RI">Rhode Island</option>
     <option <?php SelectedIfEqual($State, "SC") ?> value="SC">South Carolina</option>
     <option <?php SelectedIfEqual($State, "SD") ?> value="SD">South Dakota</option>
     <option <?php SelectedIfEqual($State, "TN") ?> value="TN">Tennessee</option>
     <option <?php SelectedIfEqual($State, "TX") ?> value="TX">Texas</option>
     <option <?php SelectedIfEqual($State, "UT") ?> value="UT">Utah</option>
     <option <?php SelectedIfEqual($State, "VT") ?> value="VT">Vermont</option>
     <option <?php SelectedIfEqual($State, "VA") ?> value="VA">Virginia</option>
     <option <?php SelectedIfEqual($State, "WA") ?> value="WA">Washington</option>
     <option <?php SelectedIfEqual($State, "DC") ?> value="DC">Washington D.C.</option>
     <option <?php SelectedIfEqual($State, "WV") ?> value="WV">West Virginia</option>
     <option <?php SelectedIfEqual($State, "WI") ?> value="WI">Wisconsin</option>
     <option <?php SelectedIfEqual($State, "WY") ?> value="WY">Wyoming</option>
    </select></td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel>Phone Number:</td>
        <td class=formtext><input name="phone" type="text" value="<?php echo htmlspecialchars(DisplayPhoneNumber($Phone)) ?>" size="30" maxlength="14" /></td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel></td>
        <td class=formtext>The information below is required to control your ad.  You must provide
        a valid email address.  A confirmation email with an activation code will be sent to the email address
        that you specify.  You cannot activate your ad unless you receive the email with the code.</p>
	<p><!-- You may only post one classified ad per email address. -->You will also create a password.  The
	password allows you to control your ad.  The classified ad will expire after 14 days or
	you can remove your ad at any time using your email address and your password. </p>
	<p>Your email address will NOT be posted in the ad.
	We will not share this contact information with anyone.</td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel>Email Address:</td>
        <td class=formtext><input name="email" type="text" id="email" value="<?php echo htmlspecialchars($Email) ?>" size="30" maxlength="40" /></td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel>Password:</td>
        <td class=formtext><input name="password1" type="password" id="password1" value="<?php echo htmlspecialchars($Password1) ?>" size="30" maxlength="16" /></td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel>Reenter Password:</td>
        <td class=formtext><input name="password2" type="password" id="password2" value="<?php echo htmlspecialchars($Password2) ?>" size="30" maxlength="16" /></td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><tr>
        <td class=formlabel>&nbsp;</td>
        <td class=formtext colspan=2><input name="ckterms" type="checkbox" <?php CheckedIfEqual($TermsAccepted, "on") ?> />
            I agree to the <a href="/index.php?content=serviceterms" target=_new>terms of use</a>.</td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr><input type="hidden" name="insert" value="insert">
      <tr>
        <td class=formlabel>&nbsp;</td>
        <td class=formtext colspan=2><input type="submit" value="Post This Ad!"></td>
      </tr><tr><td height=10 class=formlabel colspan=2><img
        src="/images/11t.gif" height=10 width=1 alt=""></td>
      </tr></form>
      </table>


<script type="text/javascript" language="JavaScript">
	inputCount(this.fm.body,this.fm.remChars,<?php echo(MAX_BODY_LEN); ?>);
</script>
      </td></tr></table>

  <!-- end main content -->
  </td></tr>
<tr><td colspan=5 height=5><img
  src="/images/11t.gif" height=15 width=1 alt=""></td></tr>

<tr><td colspan=5 style="border-style: solid; border-width: thin; border-color: #ffdf00; background: #222299;">
<!-- inlcude footer -->
<?php require_once("../common/footer.php"); ?></td></tr>
<tr><td colspan=5 height=5><img
  src="/images/11t.gif" height=5 width=1 alt=""></td></tr>
</table>
</center>

</body>
</html>
