﻿<?php
define('IN_HIGHWAY', true);
require_once(dirname(__FILE__).'/../data/config.php');
require_once(dirname(__FILE__).'/include/admin_common.inc.php');
require_once(ADMIN_ROOT_PATH.'include/admin_simple_fun.php');
$act = !empty($_GET['act']) ? trim($_GET['act']) : 'list';
$smarty->assign('act',$act);
$smarty->assign('pageheader',"微募集");
if($act == 'list')
{
	check_permissions($_SESSION['admin_purview'],"simple_list");	
	get_token();
	require_once(HIGHWAY_ROOT_PATH.'include/page.class.php');
	$key=isset($_GET['key'])?trim($_GET['key']):"";
	$key_type=isset($_GET['key_type'])?intval($_GET['key_type']):"";
	$orderbysql=" order BY  `refreshtime` DESC";
	if ($key && $key_type>0)
	{
		
		if     ($key_type==1)$wheresql=" WHERE jobname like '%{$key}%'";
		if     ($key_type==2)$wheresql=" WHERE comname like '%{$key}%'";
		if     ($key_type==3)$wheresql=" WHERE tel ='{$key}'";
		if     ($key_type==4)$wheresql=" WHERE contact like '%{$key}%'";
		$orderbysql="";
	}
	else
	{
		if (!empty($_GET['audit']))
		{
		$wheresql=" WHERE audit=".intval($_GET['audit']);
		}
		if (!empty($_GET['addtime']))
		{
			$settr=strtotime("-".intval($_GET['addtime'])." day");
			$wheresql=empty($wheresql)?" WHERE addtime> ".$settr:$wheresql." AND addtime> ".$settr;
		}
		if ($_GET['deadline']<>'')
		{
			$deadline=intval($_GET['deadline']);
			$time=time();			
			if ($deadline==0)
			{			
			$wheresql=empty($wheresql)?" WHERE deadline< {$time} AND deadline<>0 ":"{$wheresql} AND deadline< {$time} AND deadline<>0 ";
			}
			else
			{
			$settr=strtotime("+{$deadline} day");
			$wheresql=empty($wheresql)?" WHERE deadline<{$settr} AND deadline>{$time} ":"{$wheresql} AND  deadline<{$settr} AND deadline>{$time}";
			}			
		}
		if (!empty($_GET['refreshtime']))
		{
			$settr=strtotime("-".intval($_GET['refreshtime'])." day");
			$wheresql=empty($wheresql)?" WHERE refreshtime> ".$settr:$wheresql." AND refreshtime> ".$settr;
		}
	}
	
	$total_sql="SELECT COUNT(*) AS num FROM ".table('simple').$wheresql;
	$total_val=$db->get_total($total_sql);
	$page = new page(array('total'=>$total_val, 'perpage'=>$perpage,'getarray'=>$_GET));
	$currenpage=$page->nowindex;
	$offset=($currenpage-1)*$perpage;
	$list = get_simple_list($offset,$perpage,$wheresql.$orderbysql);
	$smarty->assign('key',$key);
	$smarty->assign('total',$total_val);
	$smarty->assign('list',$list);
	$smarty->assign('page',$page->show(3));
	$smarty->assign('navlabel','list');
	$smarty->display('simple/admin_simple.htm');
}
elseif($act == 'simple_del')
{
	check_permissions($_SESSION['admin_purview'],"simple_del");
	check_token();
	$id=$_REQUEST['id'];
	if (empty($id))
	{
	adminmsg("项目を選択してください！",1);
	}
	if ($num=simple_del($id))
	{
	write_log("サービス削除、削除件数".$num."行", $_SESSION['admin_name'],3);
	adminmsg("削除成功！削除件数".$num."行",2);
	}
	else
	{
	adminmsg("削除失敗！",0);
	}
}
elseif($act == 'simple_refresh')
{
	check_permissions($_SESSION['admin_purview'],"simple_refresh");
	check_token();
	$id=$_REQUEST['id'];
	if (empty($id))
	{
	adminmsg("项目を選択してください！",1);
	}
	if ($num=simple_refresh($id))
	{
	write_log("サービス更新、削除件数".$num."行", $_SESSION['admin_name'],3);
	adminmsg("更新成功！更新行数 {$num}行 ",2);
	}
	else
	{
	adminmsg("更新失敗！",0);
	}
}
elseif($act == 'jobs_perform')
{
	//审核
	if(!empty($_POST['set_audit'])){
		check_permissions($_SESSION['admin_purview'],"simple_audit");
		check_token();
		$id=$_REQUEST['id'];
		$audit=intval($_POST['audit']);
		if (empty($id))
		{
		adminmsg("项目を選択してください！",1);
		}
		if ($num=simple_audit($id,$audit))
		{
		write_log("微募集審査状態下記に設定".$audit."影響行数 {$num}行", $_SESSION['admin_name'],3);
		adminmsg("設定成功！影響行数 {$num}行 ",2);
		}
		else
		{
		adminmsg("設定失敗！",0);
		}
	}
}
elseif($act == 'simple_add')
{
	get_token();
	check_permissions($_SESSION['admin_purview'],"simple_add");
	$smarty->assign('navlabel','add');
	$smarty->display('simple/admin_simple_add.htm');
}
elseif($act == 'simple_add_save')
{
	check_token();
	check_permissions($_SESSION['admin_purview'],"simple_add");
	$setsqlarr['audit']=1;
	$setsqlarr['jobname']=trim($_POST['jobname'])?trim($_POST['jobname']):adminmsg('職位を選択してください！',1);
	$setsqlarr['amount']=intval($_POST['amount']);
	$setsqlarr['comname']=trim($_POST['comname'])?trim($_POST['comname']):adminmsg('会社の名称を入力してください！',1);
	$setsqlarr['contact']=trim($_POST['contact'])?trim($_POST['contact']):adminmsg('連絡先を入力してください！',1);
	$setsqlarr['tel']=trim($_POST['tel'])?trim($_POST['tel']):adminmsg('連絡電話入力してください！',1);
	$setsqlarr['district']=intval($_POST['district']);
	$setsqlarr['sdistrict']=intval($_POST['sdistrict']);
	$district_cn = explode("/",trim($_POST['district_cn']));
	$setsqlarr['district_cn']=$district_cn[0];
	$setsqlarr['sdistrict_cn']=$district_cn[1];
	$setsqlarr['detailed']=trim($_POST['detailed']);
	$setsqlarr['addtime']=time();
	$setsqlarr['refreshtime']=time();
	$setsqlarr['deadline']=0;
	$validity=intval($_POST['validity']);
	if ($validity>0)
	{
	$setsqlarr['deadline']=strtotime("{$validity} day");
	}
	$setsqlarr['pwd']=trim($_POST['pwd'])?trim($_POST['pwd']):adminmsg('管理パスワードを入力してください！',1);
	$setsqlarr['pwd_hash']=substr(md5(uniqid().mt_rand()),mt_rand(0,6),6);
	$setsqlarr['pwd']=md5(md5($setsqlarr['pwd']).$setsqlarr['pwd_hash'].$HW_pwdhash);
	$setsqlarr['addip']=$online_ip;
	require_once(HIGHWAY_ROOT_PATH.'include/splitword.class.php');
	$sp = new SPWord();
	$setsqlarr['key']=$setsqlarr['jobname'].$setsqlarr['comname'].$setsqlarr['address'].$setsqlarr['detailed'];
	$setsqlarr['key']="{$setsqlarr['jobname']} {$setsqlarr['comname']} ".$sp->extracttag($setsqlarr['key']);
	$setsqlarr['key']=$sp->pad($setsqlarr['key']);
	if($db->inserttable(table('simple'),$setsqlarr))
	{
		//填写管理员日志
		write_log("后台追加職位名称は : ".$setsqlarr['jobname']."の微募集 ", $_SESSION['admin_name'],3);
		$link[0]['text'] = "一覧に戻る";
		$link[0]['href'] = '?act=list';
		$link[1]['text'] = "続く追加";
		$link[1]['href'] = "?act=simple_add";
		write_log("微募集追加：".$setsqlarr['jobname'], $_SESSION['admin_name'],3);
		adminmsg("追加成功！",2,$link);
	}
	else
	{
		adminmsg("追加失敗！",0);
	}	
}
elseif($act == 'simple_edit')
{
	get_token();
	$id=intval($_REQUEST['id']);
	if (empty($id))
	{
	adminmsg("项目を選択してください！",1);
	}
	check_permissions($_SESSION['admin_purview'],"simple_edit");
	$sql = "select * from ".table('simple')." where id = '{$id}' LIMIT 1";
	$show=$db->getone($sql);
	$show['district_cn'] = $show['district_cn']."/".$show['sdistrict_cn'];
	$smarty->assign('show',$show);
	$smarty->display('simple/admin_simple_edit.htm');
}
elseif($act == 'simple_edit_save')
{
	$id=intval($_POST['id']);
	if (empty($id))
	{
	adminmsg("项目を選択してください！",1);
	}
	if ($_POST['pwd'])
	{
		$info=$db->getone("select * from ".table('simple')." where id = '{$id}' LIMIT 1");
		$setsqlarr['pwd']=md5(md5($_POST['pwd']).$info['pwd_hash'].$HW_pwdhash);
	}
	$setsqlarr['jobname']=trim($_POST['jobname'])?trim($_POST['jobname']):adminmsg('職位を選択してください！',1);
	$setsqlarr['amount']=intval($_POST['amount']);
	$setsqlarr['comname']=trim($_POST['comname'])?trim($_POST['comname']):adminmsg('会社の名称を入力してください！',1);
	$setsqlarr['contact']=trim($_POST['contact'])?trim($_POST['contact']):adminmsg('連絡先を入力してください！',1);
	$setsqlarr['tel']=trim($_POST['tel'])?trim($_POST['tel']):adminmsg('連絡電話入力してください！',1);
	$setsqlarr['district']=intval($_POST['district'])?intval($_POST['district']):adminmsg("地区選択してください");
	$setsqlarr['sdistrict']=intval($_POST['sdistrict'])?intval($_POST['sdistrict']):adminmsg("地区選択してください");
	$district_cn = explode("/",trim($_POST['district_cn']));
	$setsqlarr['district_cn']=$district_cn[0];
	$setsqlarr['sdistrict_cn']=$district_cn[1];
	$setsqlarr['detailed']=trim($_POST['detailed']);
	$setsqlarr['refreshtime']=time();
	$days=intval($_POST['days']);
	if ($days>0)
	{
	$time=$_POST['olddeadline']>time()?$_POST['olddeadline']:time();
	$setsqlarr['deadline']=strtotime("{$days} day",$time);
	}
	require_once(HIGHWAY_ROOT_PATH.'include/splitword.class.php');
	$sp = new SPWord();
	$setsqlarr['key']=$setsqlarr['jobname'].$setsqlarr['comname'].$setsqlarr['address'].$setsqlarr['detailed'];
	$setsqlarr['key']="{$setsqlarr['jobname']} {$setsqlarr['comname']} ".$sp->extracttag($setsqlarr['key']);
	$setsqlarr['key']=$sp->pad($setsqlarr['key']);
	if($db->updatetable(table('simple'),$setsqlarr," id='{$id}' "))
	{
		//填写管理员日志
		write_log("変更idは".$id."の微募集 ", $_SESSION['admin_name'],3);
		$link[0]['text'] = "一覧に戻る";
		$link[0]['href'] = '?act=list';
		write_log("変更idは：".$id."微募集情報", $_SESSION['admin_name'],3);
		adminmsg("変更成功！",2,$link);
	}
	else
	{
	adminmsg("変更失敗！",0);
	}
}
?>
