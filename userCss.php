<?php
	include('../config.php');
	header("Content-type: text/css; charset: UTF-8");
    $colorObj = json_decode($_COOKIE['JSONWrapperColorObj']);
    $appColor = '#EE4884';

    $sql = 'SELECT * FROM tb_user';
	$retval = mysql_query( $sql, $conn );
	if(mysql_num_rows($retval)>0){
		while($row = mysql_fetch_array($retval)) {
			echo '.u_'.$row["id"].':after{content:"'.substr($row["firstname"],0,1).'";}';
			$name = $row["firstname"]." ".$row["lastname"];
			echo '.u_name'.$row["id"].':after{content:"'.$name.'"}';
			echo '.u_email'.$row["id"].':after{content:"'.$row["email"].'"}';
			echo '.u_back'.$row["id"].'{background-color:'.$row["color"].' !important; }';
		}
	}
	function shadeColor ($color, $percent) {
		$color = Str_Replace("#",Null,$color);
		$r = Hexdec(Substr($color,0,2));
		$g = Hexdec(Substr($color,2,2));
		$b = Hexdec(Substr($color,4,2));
		$r = (Int)($r*(100+$percent)/100);
		$g = (Int)($g*(100+$percent)/100);
		$b = (Int)($b*(100+$percent)/100);
		$r = Trim(Dechex(($r<255)?$r:255));  
		$g = Trim(Dechex(($g<255)?$g:255));  
		$b = Trim(Dechex(($b<255)?$b:255));
		$r = ((Strlen($r)==1)?"0{$r}":$r);
		$g = ((Strlen($g)==1)?"0{$g}":$g);
		$b = ((Strlen($b)==1)?"0{$b}":$b);
		return (String)("#{$r}{$g}{$b}");
	}
	echo '.mainPanel .heading,.launch a{
		background: linear-gradient(to right,'.$appColor.','.shadeColor($appColor,50).');
	}';
?>
/* App Css*/
.dropbtn.selectedBtn,.stokeWidth.selected,.stokeWidth.selected:hover,.message-send,.chatbox-icons .fa,.chatIcon,.launch a,.leftNav,.input-group-addon,.mainPanel .heading,.inputJsonDataHeading,.btn-default,.btn-primary,.nextFileBtn:hover,.updatebtn,.filterMenuOption .active,.tabsActiveCls,.postComment, .replyComment{
	background-color: <?php echo $appColor; ?>;	
}
.changeView i,.selectFile i.nextIcon,ul.tabs li a,span.clearHistory i,footer,.loaderspin.panel i,.breadCrumb,.selected i,.btnSelected i,.btnSelected{
	color:<?php echo $appColor; ?>;
}
.form-control,.form-control:focus,input[type=text]:focus, input[type=password]:focus,.mainImageView,.btn-primary,.selectFile{
	border-color: <?php echo $appColor; ?>;
}
.commentListBorder {
	border-left-color: <?php echo $appColor; ?> !important;
}
.chatIcon {
	box-shadow: 0 0 3px 0px <?php echo $appColor; ?>;
}

/*canvas css */

.btnSelected{
	background-color: <?php echo $appColor; ?>;	
}
.stokeWidth.btnSelected{
	background-color: <?php echo $appColor; ?> !important;	
}