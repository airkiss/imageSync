#!/usr/bin/php -q
<?php
require_once('autoload.php');
function tryLock($lock_file)
{
        if(@symlink('/proc/'.getmypid(),$lock_file) !== FALSE)
                return true;
        if(is_link($lock_file) && !is_dir($lock_file))
        {
                unlink($lock_file);
                return tryLock($lock_file);
        }
        return false;
}

function CheckLock($filename)
{
        $lock_file = '/tmp/'.basename($filename).'.lock';
        if(!tryLock($lock_file))
                die(basename($filename).' is running'."\n");
        register_shutdown_function('unlink',$lock_file);
}

function ConvertThumb($originPath,$targetPath,$base_name)
{
	$filename = $originPath . $base_name;
	$filename2 = $targetPath . $base_name;
	$img = new Imagick($filename);
	$w = $img->getImageWidth();
	$h = $img->getImageHeight();
	if($w > 200 or $h > 200)
	{
		$img->scaleImage(200,200,true);
	}
	$img->setImageCompression(Imagick::COMPRESSION_JPEG); 
	$img->setImageCompressionQuality(80);
	$img->stripImage();
	$img->writeImage($filename2);
	$img->destroy(); 
}

CheckLock($argv[0]);
$MyGoogleDrive = new MyGoogleDrive();
$notify = new SendNotify($debug);
$dbh = new PDO($DB['DSN'],$DB['DB_USER'], $DB['DB_PWD'],
	array( PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
	PDO::ATTR_PERSISTENT => false));
$ItemInfoDB = new ItemInfo($dbh);
$FILEPATH = "./data/";
$TARGETPATH = "/mnt/file.librick.com/";
$THUMBPATH = "/mnt/file.librick.com/thumb/";
$title = 'Librick 圖片上傳通知';
$FolderArray = $MyGoogleDrive->FindFolderID("title='imageSync' and mimeType = 'application/vnd.google-apps.folder'");
if(count($FolderArray) == 1)					# ensure the folder is only one
{
	$folderId = $FolderArray[0]->id;			# retrieve the folder id 
	$FilesInFolderArray = $MyGoogleDrive->ListFileInFolder($folderId);
	if(count($FilesInFolderArray) == 0)
	{
		echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' 沒有任何檔案。' . "\n";
		return;	
	}
	$FailedFolderArray = $MyGoogleDrive->FindFolderID("title='FailedImageSync' and mimeType = 'application/vnd.google-apps.folder'");
	if(count($FailedFolderArray) == 1)
		$FailedFolderID = $FailedFolderArray[0]->id;
	else
		$FailedFolderID = null;
	unset($FailedFolderArray);
	
	foreach($FilesInFolderArray as $fileItem)
	{
		$fileObj = $MyGoogleDrive->GetFilesMeta($fileItem->id);	# Got the file object
		if($fileObj == NULL) continue;
		if($fileObj->mimeType !== 'image/jpeg')
		{
			if($FailedFolderID != NULL)
			{
				$MyGoogleDrive->RemoveFileFromFolder($folderId,$fileItem->id);
				$MyGoogleDrive->InsertFileIntoFolder($FailedFolderID,$fileItem->id);
			}
			else
			{
				if($MyGoogleDrive->DeleteFileInFolder($folderId,$fileItem->id))
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除成功。' . "\n";
				else
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除失敗。' . "\n";
			}
			echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '. $fileObj->originalFilename.'不符格式 JPG。' . "\n";
			unset($fileObj);
			continue;	// No Match
		}
		// Todo regexp expression need to confirm
		$filename = strtolower($fileObj->originalFilename);
		preg_match('/^([^_]*)_(.*)_([0-9]*)(.*)$/',$filename,$matches);
		if(!isset($matches[1]) and !isset($matches[2]) and !isset($matches[3]) and !isset($matches[4])) {
			if($FailedFolderID != NULL)
			{
				$MyGoogleDrive->RemoveFileFromFolder($folderId,$fileItem->id);
				$MyGoogleDrive->InsertFileIntoFolder($FailedFolderID,$fileItem->id);
			}
			else
			{
				if($MyGoogleDrive->DeleteFileInFolder($folderId,$fileItem->id))
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除成功。' . "\n";
				else
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除失敗。' . "\n";
			}
			echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename.'不符格式。' . "\n";
			unset($fileObj);
			continue;	// No Match
		}
		if($matches[4] != '.jpg')	//副檔名有誤
		{
			if($FailedFolderID != NULL)
			{
				$MyGoogleDrive->RemoveFileFromFolder($folderId,$fileItem->id);
				$MyGoogleDrive->InsertFileIntoFolder($FailedFolderID,$fileItem->id);
			}
			else
			{
				if($MyGoogleDrive->DeleteFileInFolder($folderId,$fileItem->id))
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除成功。' . "\n";
				else
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除失敗。' . "\n";
			}
			echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename.'副檔名不符格式。' . "\n";
			unset($fileObj);
			continue;	// No Match
		}

		switch($matches[1])
		{
			case 'p':	//Parts
				$itemFolder = 'Parts/';
				$data = $MyGoogleDrive->downloadFile($fileItem->id);
				if($data == null)
				{
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '. $fileObj->originalFilename . '不存在。' . "\n";
					break;
				}
				file_put_contents($FILEPATH.$fileObj->originalFilename,$data);
				$message = '['.date('Y-m-d H:i:s').'] '.__METHOD__.' 偵測到有'. $fileObj->originalFilename . '，準備更新至S3->';
				$librick_id = $matches[1] . '_' . $matches[2];
				$numArray = $ItemInfoDB->ReadDB($librick_id);
				if($numArray['max'] != -1)	//No Error
				{
					if($numArray['title'] == false)	// title no setup
					{
						$newFileName = $librick_id.'_'.$matches[3].$matches[4];
						if($numArray['max'] == 0)
							$ItemInfoDB->AddImageNo($librick_id,$matches[3]);
						else
						{
							if($matches[3] == 1)	//title setup
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3],true);
							else
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3]);
						}
					}
					else
					{
						$image_no = $numArray['max'] + 1;
						$newFileName = $librick_id.'_'.$image_no.$matches[4];
						$ItemInfoDB->UpdateImageNo($librick_id,$image_no);
					}
					rename($FILEPATH.$fileObj->originalFilename,$FILEPATH.$newFileName);
					ConvertThumb($FILEPATH,$FILEPATH."thumb/",$newFileName);
					rename($FILEPATH.$newFileName,$TARGETPATH.$itemFolder.$newFileName);
					rename($FILEPATH.'thumb/'.$newFileName,$THUMBPATH.$itemFolder.$newFileName);
					$message.='成功。';
					if($MyGoogleDrive->DeleteFileInFolder($folderId,$fileItem->id))
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除成功。' . "\n";
					else
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除失敗。' . "\n";
				}
				else $message.='失敗。';
				echo $message."\n";
				break;
			case 'b':	//Boxes
				$itemFolder = 'Boxes/';
				$data = $MyGoogleDrive->downloadFile($fileItem->id);
				if($data == null)
				{
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '. $fileObj->originalFilename . '不存在。' . "\n";
					break;
				}
				file_put_contents($FILEPATH.$fileObj->originalFilename,$data);
				$message = '['.date('Y-m-d H:i:s').'] '.__METHOD__.' 偵測到有'. $fileObj->originalFilename . '，準備更新至S3->';
				$librick_id = $matches[1] . '_' . $matches[2];
				$numArray = $ItemInfoDB->ReadDB($librick_id);
				if($numArray['max'] != -1)	//No Error
				{
					if($numArray['title'] == false)	// title no setup
					{
						$newFileName = $librick_id.'_'.$matches[3].$matches[4];
						if($numArray['max'] == 0)
							$ItemInfoDB->AddImageNo($librick_id,$matches[3]);
						else
						{
							if($matches[3] == 1)	//title setup
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3],true);
							else
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3]);
						}
					}
					else
					{
						$image_no = $numArray['max'] + 1;
						$newFileName = $librick_id.'_'.$image_no.$matches[4];
						$ItemInfoDB->UpdateImageNo($librick_id,$image_no);
					}
					rename($FILEPATH.$fileObj->originalFilename,$FILEPATH.$newFileName);
					ConvertThumb($FILEPATH,$FILEPATH."thumb/",$newFileName);
					rename($FILEPATH.$newFileName,$TARGETPATH.$itemFolder.$newFileName);
					rename($FILEPATH.'thumb/'.$newFileName,$THUMBPATH.$itemFolder.$newFileName);
					$message.='成功。';
					if($MyGoogleDrive->DeleteFileInFolder($folderId,$fileItem->id))
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除成功。' . "\n";
					else
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除失敗。' . "\n";
				}
				else $message.='失敗。';
				echo $message."\n";
				break;
			case 'm':	//Minifigs
				$itemFolder = 'Minifigs/';
				$data = $MyGoogleDrive->downloadFile($fileItem->id);
				if($data == null)
				{
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '. $fileObj->originalFilename . '不存在。' . "\n";
					break;
				}
				file_put_contents($FILEPATH.$fileObj->originalFilename,$data);
				$message = '['.date('Y-m-d H:i:s').'] '.__METHOD__.' 偵測到有'. $fileObj->originalFilename . '，準備更新至S3->';
				$librick_id = $matches[1] . '_' . $matches[2];
				$numArray = $ItemInfoDB->ReadDB($librick_id);
				if($numArray['max'] != -1)	//No Error
				{
					if($numArray['title'] == false)	// title no setup
					{
						$newFileName = $librick_id.'_'.$matches[3].$matches[4];
						if($numArray['max'] == 0)
							$ItemInfoDB->AddImageNo($librick_id,$matches[3]);
						else
						{
							if($matches[3] == 1)	//title setup
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3],true);
							else
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3]);
						}
					}
					else
					{
						$image_no = $numArray['max'] + 1;
						$newFileName = $librick_id.'_'.$image_no.$matches[4];
						$ItemInfoDB->UpdateImageNo($librick_id,$image_no);
					}
					rename($FILEPATH.$fileObj->originalFilename,$FILEPATH.$newFileName);
					ConvertThumb($FILEPATH,$FILEPATH."thumb/",$newFileName);
					rename($FILEPATH.$newFileName,$TARGETPATH.$itemFolder.$newFileName);
					rename($FILEPATH.'thumb/'.$newFileName,$THUMBPATH.$itemFolder.$newFileName);
					$message.='成功。';
					if($MyGoogleDrive->DeleteFileInFolder($folderId,$fileItem->id))
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除成功。' . "\n";
					else
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除失敗。' . "\n";
				}
				else $message.='失敗。';
				echo $message."\n";
				break;
			case 'g':	//Gears
				$itemFolder = 'Gears/';
				$data = $MyGoogleDrive->downloadFile($fileItem->id);
				if($data == null)
				{
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '. $fileObj->originalFilename . '不存在。' . "\n";
					break;
				}
				file_put_contents($FILEPATH.$fileObj->originalFilename,$data);
				$message = '['.date('Y-m-d H:i:s').'] '.__METHOD__.' 偵測到有'. $fileObj->originalFilename . '，準備更新至S3。';
				$librick_id = $matches[1] . '_' . $matches[2];
				$numArray = $ItemInfoDB->ReadDB($librick_id);
				if($numArray['max'] != -1)	//No Error
				{
					if($numArray['title'] == false)	// title no setup
					{
						$newFileName = $librick_id.'_'.$matches[3].$matches[4];
						if($numArray['max'] == 0)
							$ItemInfoDB->AddImageNo($librick_id,$matches[3]);
						else
						{
							if($matches[3] == 1)	//title setup
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3],true);
							else
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3]);
						}
					}
					else
					{
						$image_no = $numArray['max'] + 1;
						$newFileName = $librick_id.'_'.$image_no.$matches[4];
						$ItemInfoDB->UpdateImageNo($librick_id,$image_no);
					}
					rename($FILEPATH.$fileObj->originalFilename,$FILEPATH.$newFileName);
					ConvertThumb($FILEPATH,$FILEPATH."thumb/",$newFileName);
					rename($FILEPATH.$newFileName,$TARGETPATH.$itemFolder.$newFileName);
					rename($FILEPATH.'thumb/'.$newFileName,$THUMBPATH.$itemFolder.$newFileName);
					$message.='成功。';
					if($MyGoogleDrive->DeleteFileInFolder($folderId,$fileItem->id))
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除成功。' . "\n";
					else
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除失敗。' . "\n";
				}
				else $message.='失敗。';
				echo $message."\n";
				break;
			case 's':	//Sets
				$itemFolder = 'Sets/';
				$data = $MyGoogleDrive->downloadFile($fileItem->id);
				if($data == null)
				{
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '. $fileObj->originalFilename . '不存在。' . "\n";
					break;
				}
				file_put_contents($FILEPATH.$fileObj->originalFilename,$data);
				$message = '['.date('Y-m-d H:i:s').'] '.__METHOD__.' 偵測到有'. $fileObj->originalFilename . '，準備更新至S3。';
				$librick_id = $matches[1] . '_' . $matches[2];
				$numArray = $ItemInfoDB->ReadDB($librick_id);
				if($numArray['max'] != -1)	//No Error
				{
					if($numArray['title'] == false)	// title no setup
					{
						$newFileName = $librick_id.'_'.$matches[3].$matches[4];
						if($numArray['max'] == 0)
							$ItemInfoDB->AddImageNo($librick_id,$matches[3]);
						else
						{
							if($matches[3] == 1)	//title setup
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3],true);
							else
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3]);
						}
					}
					else
					{
						$image_no = $numArray['max'] + 1;
						$newFileName = $librick_id.'_'.$image_no.$matches[4];
						$ItemInfoDB->UpdateImageNo($librick_id,$image_no);
					}
					rename($FILEPATH.$fileObj->originalFilename,$FILEPATH.$newFileName);
					ConvertThumb($FILEPATH,$FILEPATH."thumb/",$newFileName);
					rename($FILEPATH.$newFileName,$TARGETPATH.$itemFolder.$newFileName);
					rename($FILEPATH.'thumb/'.$newFileName,$THUMBPATH.$itemFolder.$newFileName);
					$message.='成功。';
					if($MyGoogleDrive->DeleteFileInFolder($folderId,$fileItem->id))
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除成功。' . "\n";
					else
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除失敗。' . "\n";
				}
				else $message.='失敗。';
				echo $message."\n";
				break;
			case 'stk':	//Stickers
				$itemFolder = 'Stickers/';
				$data = $MyGoogleDrive->downloadFile($fileItem->id);
				if($data == null)
				{
					echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '. $fileObj->originalFilename . '不存在。' . "\n";
					break;
				}
				file_put_contents($FILEPATH.$fileObj->originalFilename,$data);
				$message = '['.date('Y-m-d H:i:s').'] '.__METHOD__.' 偵測到有'. $fileObj->originalFilename . '，準備更新至S3。';
				$librick_id = $matches[1] . '_' . $matches[2];
				$numArray = $ItemInfoDB->ReadDB($librick_id);
				if($numArray['max'] != -1)	//No Error
				{
					if($numArray['title'] == false)	// title no setup
					{
						$newFileName = $librick_id.'_'.$matches[3].$matches[4];
						if($numArray['max'] == 0)
							$ItemInfoDB->AddImageNo($librick_id,$matches[3]);
						else
						{
							if($matches[3] == 1)	//title setup
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3],true);
							else
								$ItemInfoDB->UpdateImageNo($librick_id,$matches[3]);
						}
					}
					else
					{
						$image_no = $numArray['max'] + 1;
						$newFileName = $librick_id.'_'.$image_no.$matches[4];
						$ItemInfoDB->UpdateImageNo($librick_id,$image_no);
					}
					rename($FILEPATH.$fileObj->originalFilename,$FILEPATH.$newFileName);
					ConvertThumb($FILEPATH,$FILEPATH."thumb/",$newFileName);
					rename($FILEPATH.$newFileName,$TARGETPATH.$itemFolder.$newFileName);
					rename($FILEPATH.'thumb/'.$newFileName,$THUMBPATH.$itemFolder.$newFileName);
					$message.='成功。';
					if($MyGoogleDrive->DeleteFileInFolder($folderId,$fileItem->id))
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除成功。' . "\n";
					else
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除失敗。' . "\n";
				}
				else $message.='失敗。';
				echo $message."\n";
				break;
			default:
				$message = '['.date('Y-m-d H:i:s').'] '.__METHOD__.' 未實作 '.$fileObj->originalFilename .' 無法執行 -> 失敗。';
				if($FailedFolderID != NULL)
				{
					$MyGoogleDrive->RemoveFileFromFolder($folderId,$fileItem->id);
					$MyGoogleDrive->InsertFileIntoFolder($FailedFolderID,$fileItem->id);
				}
				else
				{
					if($MyGoogleDrive->DeleteFileInFolder($folderId,$fileItem->id))
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除成功。' . "\n";
					else
						echo '['.date('Y-m-d H:i:s').'] '.__METHOD__.' '.$fileObj->originalFilename . '刪除失敗。' . "\n";
				}
				echo $message."\n";
				break;
		}	// End of Swticha
		unset($data);
		unset($fileObj);
	}	// End of foreach
	$message = '['.date('Y-m-d H:i:s').'] '.'圖片上傳批次更新已完成作業。';
	$notify->pushNote($title,$message);
}
else
	echo '['.date('Y-m-d H:i:s').'] '.__METHOD__. ' 找不到目錄名 imageSync。' . "\n";
unset($notify);
unset($MyGoogleDrive);
?>
