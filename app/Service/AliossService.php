<?php

namespace App\Service;

use OSS\OssClient;
use OSS\Core\OssException;
use Log;
use OSS\Model\PrefixInfo;
use OSS\Model\ObjectInfo;

class AliossService
{

    private $ossClient;

    private $bucketName;

    private $AccessKeyId;

    private $AccessKeySecret;

    private $endpoint;

    //实例化
    public function __construct()
    {
        $this->bucketName = config('alioss.BucketName');
        $this->accessKeyId = config('alioss.AccessKeyId');
        $this->accessKeySecret = config('alioss.AccessKeySecret');
        $this->endpoint = 'oss-cn-shenzhen.aliyuncs.com'; //深圳
        $this->ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
    }

    //文件上传
    public static function upload($fileName, $filePath)
    {
      try {
        $oss = new AliossService();
        $res = $oss->ossClient->uploadFile($oss->bucketName, $fileName, $filePath);
        $res['info']['url'] = str_replace("offerhkok.oss-cn-shenzhen.aliyuncs.com","cdn.offerhk.com",$res['info']['url']);
          $url = $res['info']['url'];
          $url = str_replace("httpofferhkok.oss-cn-shenzhen-internal.aliyuncs.com", "offerhkok.oss-cn-shenzhen.aliyuncs.com", $url);
          if (strstr($url, 'http://')) {
              $url = str_replace("http://", "https://", $url);
          }
          Log::info($url);
          return $url;
      }catch(OssException $e) {
        Log::error('aliossError:'. $e->getMessage());
        return false;
      }
    }

    //文件删除
    public static function deleteImg($filename)
    {
        try {
            $oss = new AliossService();
            return $oss->ossClient->deleteObject($oss->bucketName, $filename);
        }catch(OssException $e) {
            Log::error('aliossError:'. $e->getMessage());
            return false;
        }
    }
    public static function getAll($bucket = ''){
        $prefix = '';
        $delimiter = '/';
        $nextMarker = '';
        $maxkeys = 30;
        while (true) {
            $options = array(
                'delimiter' => $delimiter,
                'prefix' => $prefix,
                'max-keys' => $maxkeys,
                'marker' => $nextMarker,
            );
            var_dump($options);
            try {
                $oss = new AliossService();
                $listObjectInfo = $oss->ossClient->listObjects($bucket, $options);
            } catch (OssException $e) {
                printf(__FUNCTION__ . ": FAILED\n");
                printf($e->getMessage() . "\n");
                return;
            }
            // 得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表
            $nextMarker = $listObjectInfo->getNextMarker();
            $listObject = $listObjectInfo->getObjectList();
            $listPrefix = $listObjectInfo->getPrefixList();
            var_dump($listObject); // 这里手册中写的是输出 count($listObject)，以下同理
            var_dump($listPrefix);
            if ($nextMarker === '') {
                break;
            }
        }
    }

    public static function fileList($dir = '', $maxKey = 1000, $delimiter = '/', $continuationToken = '') {
        $fileList = []; // 获取的文件列表, 数组的一阶表示分页结果
        $dirList = []; // 获取的目录列表, 数组的一阶表示分页结果
        $storageList = [
            'file' => [], // 真正的文件数组
            'dir'  => [], // 真正的目录数组
        ];
        Log::info('开始查找oss文件');
        while (true) {
            $options = [
                'delimiter' => $delimiter,
                'prefix'    => $dir,
                'max-keys'  => $maxKey,
                'continuation-token'    => $continuationToken,
            ];
            $oss = new AliossService();
            try {
                //$fileListInfo = $oss->ossClient->ListObjects('offer-backup', $options);
                $fileListInfo = $oss->ossClient->listObjectsV2('offer-backup', $options);
            } catch (OssException $e) {
                return $e->getMessage(); // 发送错误信息
            }
            //$nextMarker = $fileListInfo->getNextMarker();
            $continuationToken = $fileListInfo->getNextContinuationToken();
            $fileItem = $fileListInfo->getObjectList();
            $dirItem = $fileListInfo->getPrefixList();
            $fileList = array_merge($fileList,$fileItem);
            $dirList = array_merge($dirList,$dirItem);
            if ($fileListInfo->getIsTruncated() === 'false') break;
        }
        if(!empty($fileList))foreach ($fileList as $item){
            $storageList['file'][] = $oss->objectInfoParse($item);
        }
        if(!empty($dirList))foreach ($dirList as $item){
            $storageList['dir'][] = $oss->prefixInfoParse($item);
        }
        Log::info('查找结束');
        return $storageList; // 发送正确信息
    }
    /* 解析 prefixInfo 类 */
    private function prefixInfoParse(PrefixInfo $prefixInfo){
        return [
            'dir' => $prefixInfo->getPrefix(),
        ];
    }
    /* 解析 objectInfo 类 */
    private function objectInfoParse(ObjectInfo $objectInfo) {
        return [
            'name'      => $objectInfo->getKey(),
            'size'      => $objectInfo->getSize(),
            'update_at' => $objectInfo->getLastModified(),
        ];
    }
    public static function deleteObject($bucket = 'offer-backup', $object){
        if($object){
            $oss = new AliossService();
            $oss->ossClient->deleteObject($bucket, $object);
        }
        return true;
    }
}
