<?php
namespace app\wap\model;
use think\Model;
use think\Db;

class CategoryModel extends Model
{
    protected $name='category';
    
    public function getCategory($status = 0,$nav = 0, $isarea = true) {
	    $where = [];
	    if ($status) {
	        $where['status'] = $status;
	    }
	    if ($nav) {
		    $where['nav'] = ($nav == 1 || $nav == 2) ? array('IN', [$nav, 3]) : array('IN', [1, 2, 3]); 
	    }
	    $cate_arr = $this->where($where)->order('sort asc')->select();
	    foreach ($cate_arr as $k => $v) {
	    	$cate_arr[$k] = $this->getCategoryArea($v, [], $isarea);
	    }
	    return $cate_arr;
	}

	public function clearLink($cate) {
		$arr = array();
		foreach ($cate as $v) {
			if ($v['jumpurl'] != '') {
				$arr[] = $v;
			}
		}
		return $arr;		
	}
	//组成多维数组
	public function unlimitedForLayer($cate, $name = 'child', $pid = 0){
		$arr = array();
		foreach ($cate as $v) {
			if ($v['pid'] == $pid) {
				$v[$name] = $this->unlimitedForLayer($cate, $name, $v['id']);
				$arr[] = $v;
			}
		}
		return $arr;
	}
	//获取指定分类的子集
	public function getChildsId($cate, $pid, $flag = 0) {
	    $arr = array();
	    if ($flag) {
	        $arr[] = $pid;
	    }
	    foreach ($cate as $v) {
	        if ($v['pid'] == $pid) {
	            $arr[] = $v['id'];
	            $arr = array_merge($arr, $this->getChildsId($cate, $v['id']));
	        }
	    }
	    return $arr;
	}
	
	//传递一个分类ID返回该分类相关信息
	public function getSelf($cate, $id) {
		$arr = array();
		foreach ($cate as $v) {
			if ($v['id'] == $id) {
				$arr = $v;
				return $arr;
			}
		}
		return $arr;
	}
	//传递一个子分类ID返回他的所有父级分类
	public function getParents($cate, $id) {
		$arr = array();
		foreach ($cate as $v) {
			if ($v['id'] == $id) {
				$arr[] = $v;
				$arr = array_merge($this->getParents($cate, $v['pid']), $arr);
			}
		}
		return $arr;
	}

	public function getCategoryUrl($cate, $area = []) {
	    $url = '';
	    //如果是跳转，直接就返回跳转网址
	    if (!empty($cate['jumpurl'])) {
	    	if (substr($cate['jumpurl'], 0,1) == '@') {
	    		$cate = $this->getOneCategory(substr($cate['jumpurl'], 1));
	    	}else{
	    		return $cate['jumpurl'];
	    	}
	    }
	    $cname = $cate['etitle'] ? $cate['etitle'] : $cate['id'];
	    if (!$area) {
            $area = config('sys.sys_area')  ? db('area')->where(['etitle'=>config('sys.sys_area')])->find() : [];
        }

	    switch (config('sys.url_model')) {
	    	case '1'://动态
	    		$urlqz = config('sys.sys_levelurl') == 'm' ? '' : '/wap'; //url前缀
	    		$url = "/index.php".$urlqz."/category/index?id=".$cate['id'];//url('Category/index', array('id' => $cate['id']));
	    		if ($area) {
		    		$url = $url. "&area=".$area['etitle'];
	    		}
	    		break;
	    	case '3'://伪静态
	    		$urlqz = config('sys.sys_levelurl') == 'm' ? '' : '/m'; //url前缀
		        $url = $cate['etitle'] ? $cate['etitle'].'/' : $cate['id'].'/';
		        if ($area) {
		    		$url = $urlqz."/".$area['etitle']."_".$url;
		    	}else{
		    		$url = $urlqz."/".$url;
		    	}
	    		break;
	    }
	    return $url;
	}

	public function getOneCategory($id)
    {
        return $this->where(['id'=>$id])->find();
    }

    public function getCategoryArea($cate, $area = [], $isarea = true)
    {
    	if (!$area) {
    		$area = config('sys.sys_area') ? db('area')->where(['etitle'=>config('sys.sys_area')])->find() : [];
    	}
        if ($area && $isarea) {
			$cate['title'] = $cate['isarea'] ? $area['stitle'].$cate['title'] : $cate['title'];
        }

        $cate['target'] = $cate['target'] ? '_blank' : '_self';
		$cate['url'] = $this->getCategoryUrl($cate, $area);
        return $cate;
    }
}