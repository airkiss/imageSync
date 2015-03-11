<?php
class ItemInfo {
	private $dbh = null;
	function __construct($dbh)
	{
		$this->dbh = $dbh;
//		$this->dbh = new PDO($DB['DSN'],$DB['DB_USER'], $DB['DB_PWD'],
//				array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
//					PDO::ATTR_PERSISTENT => false));
		# 錯誤的話, 就不做了
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
	}

	function __destruct()
	{
		
	}

	function getItemInfo()
	{
		try {
			$p = $this->dbh->prepare("select id,item_type,image_no from item_info where 
					item_type not in ('Boxes','Instructions') order by length(image_no)");
			$p->execute();	
			$resData = $p->fetchAll(PDO::FETCH_OBJ);
			return $resData;
		} catch (PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/ItemInfo.txt");
			return null;
		}
	}

	function ReadDB($librick_id)
	{
		try {
			$p = $this->dbh->prepare("select id,image_no from item_info where id=:id");
			$p->execute(array('id'=>$librick_id));	
			$resData = $p->fetch(PDO::FETCH_ASSOC);
			if(!empty($resData['image_no']))
			{
				$tmpArray = explode(',',$resData['image_no']);
				return array('max'=>max($tmpArray),'title'=>(min($tmpArray)==1)?true:false);
			}
			else
				return array('max'=>0,'title'=>false);
		} catch (PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/ItemInfo.txt");
			return array('max'=>-1,'title'=>false);
		}
		#error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Finish'."\n",3,"./log/ItemInfo.txt");
		return array('max'=>-1,'title'=>false);
	}

	function AddImageNo($librick_id,$image_no)
	{
		try {
			$p = $this->dbh->prepare("update item_info set image_no = :image_no where id=:id");
			$p->execute(array('id'=>$librick_id,'image_no'=>$image_no));
		} catch (PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/ItemInfo.txt");
			return false;
		}
		#error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Finish'."\n",3,"./log/ItemInfo.txt");
		return true;
		
	}

	function UpdateImageNo($librick_id,$image_no,$isFirst=false)
	{
		try {
			if($isFirst)
				$p = $this->dbh->prepare("update item_info set image_no = concat(:image_no,',',image_no) where id=:id");
			else
				$p = $this->dbh->prepare("update item_info set image_no = concat(image_no,',',:image_no) where id=:id");
			$p->execute(array('id'=>$librick_id,'image_no'=>$image_no));
		} catch (PDOException $e) {
			error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Error: ('.$e->getLine().') ' . $e->getMessage()."\n",3,"./log/ItemInfo.txt");
			return false;
		}
		#error_log('['.date('Y-m-d H:i:s').'] '.__METHOD__.' Finish'."\n",3,"./log/ItemInfo.txt");
		return true;
		
	}
}
?>
