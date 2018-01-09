<?php
namespace Api\Controller;
use Think\Controller;
class IndexController extends PublicController {
	//***************************
	//  首页数据接口
	//***************************
    public function index(){
    	//如果缓存首页没有数据，那么就读取数据库
    	/***********获取首页顶部轮播图************/
    	$ggtop=M('guanggao')->order('sort desc,id asc')->field('id,name,photo')->limit(10)->select();
		foreach ($ggtop as $k => $v) {
			$ggtop[$k]['photo']=__DATAURL__.$v['photo'];
			$ggtop[$k]['name']=urlencode($v['name']);
		}
    	/***********获取首页顶部轮播图 end************/

        //======================
        //首页推荐品牌 20个
        //======================
        $brand = M('brand')->where('1=1')->field('id,name,photo')->limit(20)->select();
        foreach ($brand as $k => $v) {
            $brand[$k]['photo'] = __DATAURL__.$v['photo'];
        }

        //======================
        //首页培训课程
        //======================
        $course = M('course')->where('del=0')->order('id desc')->field('id,title,intro,photo')->select();
        foreach ($course as $k => $v) {
            $course[$k]['photo'] = __DATAURL__.$v['photo'];
        }

    	//======================
    	//首页推荐产品
    	//======================
    	$pro_list = M('product')->where('del=0 AND pro_type=1 AND is_down=0 AND type=1')->order('sort desc,id desc')->field('id,name,intro,photo_x,price_yh,price,shiyong')->limit(8)->select();
    	foreach ($pro_list as $k => $v) {
    		$pro_list[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
    	}

        //======================
        //首页分类 自己组建数组
        //======================
        $indeximg = M('indeximg')->where('1=1')->order('id asc')->field('photo')->select();
        $procat = array();
        $procat[0]['name'] = '新闻资讯';
        $procat[0]['imgs'] = __DATAURL__.$indeximg[0]['photo'];
        $procat[0]['link'] = 'other';
        $procat[0]['ptype'] = 'news';

        $procat[1]['name'] = '教学优势';
        $procat[1]['imgs'] = __DATAURL__.$indeximg[1]['photo'];
        $procat[1]['link'] = 'other';
        $procat[1]['ptype'] = 'jxys';

        $procat[2]['name'] = '学员风采';
        $procat[2]['imgs'] = __DATAURL__.$indeximg[2]['photo'];
        $procat[2]['link'] = 'other';
        $procat[2]['ptype'] = 'xyfc';

        $procat[3]['name'] = '关于我们';
        $procat[3]['imgs'] = __DATAURL__.$indeximg[3]['photo'];
        $procat[3]['link'] = 'other';
        $procat[3]['ptype'] = 'gywm';

    	echo json_encode(array('ggtop'=>$ggtop,'procat'=>$procat,'prolist'=>$pro_list,'brand'=>$brand,'course'=>$course));
    	exit();
    }

    //***************************
    //  首页产品 分页
    //***************************
    public function getlist(){
        $page = intval($_REQUEST['page']);
        $limit = intval($page*8)-8;

        $pro_list = M('product')->where('del=0 AND pro_type=1 AND is_down=0 AND type=1')->order('sort desc,id desc')->field('id,name,photo_x,price_yh,shiyong')->limit($limit.',8')->select();
        foreach ($pro_list as $k => $v) {
            $pro_list[$k]['photo_x'] = __DATAURL__.$v['photo_x'];
        }

        echo json_encode(array('prolist'=>$pro_list));
        exit();
    }

    public function ceshi(){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<32;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        echo $str;
    }


    public function wxupload(){
        $upload_res=$this->upload();
        if($upload_res['flag']=='success'){
            $data['pic']="Uploads/report/".$upload_res['result'];
            apiResponse("success","上传成功！",$data);
        }else{
            apiResponse("error","上传失败！");
        }
    }

    /**
     * 处理商品图片上传
     */
    public function upload(){
        if(empty($_FILES['pic']['name'])){
            $is_upload=false;
        }else{
            $is_upload=true;
        }
        /*foreach($_FILES['pic']['name'] as $k=>$v){
            if(!empty($v))$is_upload=true;
        }*/
        if($is_upload){
            //load("@.function.php");
            $upload_res=$this->uploadThemeImg('report');
            if(empty($upload_res['error'])){
                return array('flag'=>'success','result'=>$upload_res[0]);
            }else{
                return array('flag'=>'error','result'=>$upload_res['error']);//$this->error($upload_res['error']);
            }
        }else{
            return array('flag'=>'no');
        }
    }

    /**
     * 上传图片公共函数
     */
    function uploadThemeImg($file){

        //load("@.uploadfile");
        //include_once 'uploadfile.php';
        $save_path = "./Uploads/".$file."/".date('Ym')."/";
        //$save_path = "./Uploads/".$file."/201404/";
        $upload_info = $this->getUpLoadFiles('',$save_path,'','','200','200','');
        if(count($upload_info[0])<=1){
            return array('error'=>$upload_info);
        }else{
            foreach($upload_info as $k=>$v){
                $url_arr[]=date('Ym')."/".$v['savename'];
            }
        }
        return $url_arr;
    }



    /*
 * by king 2013年5月10日15:08:49
 * 自定义 简单上传类
 * 参数：$name-定义文件上传命名规则
 *      $url-原图保存地址
 *      $maxsize-文件最大 大小
 *      $type-上传文件类型
 *      $width-缩略图宽
 *      $height-缩略图高
 *      $thumb_pre-缩略图前坠名
 * 成功返回 上传后的信息
 * 失败返回异常名称
 * */
    function getUpLoadFiles($name,$url,$maxsize,$type,$width,$height,$thumb_pre,$is_thumb=false)
    {
        $upload = new \Think\UploadFile();
        $upload->maxSize        = !empty($maxsize)?$maxsize:20480000;
        $upload->allowExts      = is_array($type)?$type:array('jpg','png','jpeg','bmp','gif');
        $upload->savePath       = isset($url)?$url:'./Uploads'.date("Ym").'/';
        $upload->saveRule       = !empty($name)?$name:'uniqid';       //保存文件命名规则 如果不是规则的关键字 默认设为上传的文件名称

        if($is_thumb)
        {
            //生成缩略图
            $upload->thumb          = true;
            $upload->thumbPath      = isset($url)?$url:'./Uploads'.date("Ym").'/';
            $upload->thumbPrefix    = !empty($thumb_pre)?$thumb_pre:'thumb_';
            $upload->thumbMaxWidth  = $width;
            $upload->thumbMaxHeight = $height;
            $upload->uploadReplace = true;
        }
        if($upload->Upload())
        {
            $info = $upload->getUploadFileInfo();
            return $info;
        }
        else
        {
            return $upload->getErrorMsg();
        }
    }

    function apiResponse($flag = 'error', $message = '',$data = array()){
        $result = array('flag'=>$flag,'message'=>$message,'data'=>$data);
        print json_encode($result);exit;
    }


}