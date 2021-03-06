<?php
	/*

	避免版权纠纷
	最早文件头部

	EDLM个人发卡网3.5
	作者：MadDog
	QQ：3283404596
	WX：Edi13146

	未经同意请勿利用本程序采取转载、出售、等宣传手段以及盈利手段！

	*/
	namespace app\kmad\controller;

	use think\Db;
	use think\Controller;
	use think\Session;
	use phpmailer\phpmailer;

	class Index extends Controller
	{
		//私有
		//身份验证
		private function auth(){
			$data['code'] = -1;
			if(Session::has('token')){
				$t = Db::table('admin')->where('id',1)->find();
				if(Session::get('token')==$t['token']){
					$data['code'] = 0;
				}
			}
			return $data;
		}
		//获取当前页面
		private function a($mod){
			if($mod===''){
		        $active = 0;
		    }else if($mod==='c'){
		        $active = 1;
		    }else if($mod==='type'){
		        $active = 2;
		    }else if($mod==='sp'){
		        $active = 3;
		    }else if($mod==='newkm'){
		        $active = 4;
		    }else if($mod==='unsold'){
		        $active = 5;
		    }else if($mod==='sold'){
		        $active = 6;
		    }else if($mod==='admin'){
		        $active = 7;
		    }else if($mod==='mc'){
		        $active = 8;
		    }else if($mod==='dd'){
		        $active = 9;
		    }else if($mod==='dds'){
		        $active = 10;
		    }else{
		        $active = 0;
		    }
		    return $active;
		}
		public function JsAlert($msg,$url){
	        if($msg!=""){
	            $result = '<script>alert("'.$msg.'");location.href="'.$url.'";</script>';
	        }else{
	            $result = '<script>location.href="'.$url.'";</script>';
	        }
	        return $result;
	    }
		//私有
		//
	    public function index()
	    {
	    	$tz = input('tz');
	    	if(isset($tz)){
	    		return $this->JsAlert('',request()->url(true).'/index');
	    	}
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	$today = strtotime(date("Y-m-d 00:00:00",time()));
		    //获取数据
		    $data['zfbc'] = Db::table('orders')->where('type','alipay')->whereNotNull('win_order')->whereTime('stime', 'today')->sum('money');
	    	$data['wxc'] = Db::table('orders')->where('type','wxpay')->whereNotNull('win_order')->whereTime('stime', 'today')->sum('money');
	    	$data['qqc'] = Db::table('orders')->where('type','qqpay')->whereNotNull('win_order')->whereTime('stime', 'today')->sum('money');
	    	//
	    	$data['zfbb'] = Db::table('orders')->where('type','alipay')->whereNotNull('win_order')->whereTime('stime', 'today')->count();
	    	$data['wxb'] = Db::table('orders')->where('type','wxpay')->whereNotNull('win_order')->whereTime('stime', 'today')->count();
	    	$data['qqb'] = Db::table('orders')->where('type','qqpay')->whereNotNull('win_order')->whereTime('stime', 'today')->count();
	    	//
	    	$data['z'] = Db::table('orders')->whereNotNull('win_order')->sum('money');
	    	//
	    	$data['kmc'] = Db::table('kms')->whereNull('order')->count();
		    $data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function mc()
	    {
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	//
	    	$data['kml'] = Db::table('kms k')->field('k.*,(select title from lists where lists.id = k.spid) AS name')->limit(50)->whereNotNull('order')->order('time desc')->select();
		    $data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function dd()
	    {
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	$data['to'] = '点我可通过商品筛选';
	    	$data['order'] = Db::table('orders o')->whereNotNull('win_order')->field('o.*,(select title from lists where lists.id = o.spid) AS title');
	    	$tid = '';
	    	if(!empty($_GET['tid'])){
	    		$tid = (int)$_GET['tid'];
	    		$data['to'] = '当前：'.Db::table('lists')->where('id',(int)$_GET['tid'])->find()['title'];
	    		if($data['to']){
	    			$data['order'] = $data['order']->where('spid',(int)$_GET['tid']);
	    		}
	    	}
	    	if(!empty($_GET['order'])){
	    		$data['order'] = $data['order']->where(array(
	    			'order' => $_GET['order']
	    		));
	    	}
	    	//
	    	$data['lists'] = Db::table('lists')->order('or desc')->select();
	    	$data['order'] = $data['order']->order('time desc')->paginate(18,false,[
	    		'query' => [
	    			'tid' => $tid
	    		]
	    	]);
		    $data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function dds()
	    {
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	//
	    	if(isset($_GET['ql'])){
	    		$yesterday = date("Y-m-d H:i:s",strtotime("-1 day"));
	    		Db::table('orders')->where(array(
	    			'stime' => ['<=',$yesterday],
                    'win_order' => NULL,
                    'type' => NULL
	    		))->delete();
	    	}else if(isset($_GET['bd'])){
	    		$s = Db::table('orders')->where('id',(int)$_GET['bd'])->find();
	    		$us = Db::table('orders')->where(array(
                    'id' => (int)$_GET['bd'],
                    'win_order' => NULL,
                    'type' => NULL
                ))->update([
                    'win_order' => '后台手动补单',
                    'type' => '手动'
                ]);
                if($us){
                    Db::table('kms')->where(array(
                        'spid'=>$s['spid'],
                        'order' => NULL
                    ))->limit($s['time'])->update([
                        'order' => $s['order']
                    ]);
                    $checkmail="/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";            
                    if(preg_match($checkmail,$s['pwd'])){
                        //发送邮件
                        Vendor('phpmailer.phpmailer');
                        $mail = new PHPMailer();
                        $cfs = Db::table('config')->find(1);
                        $lp = Db::table('admin')->find(1);
                        $ckms = Db::table('kms')->where(array('order'=>$s['order']))->select();
                        $kmds = '您本次购买到的卡密如下:<br>';
                        foreach ($ckms as $v) {
                            $kmds.= $v['km'].'<br>';
                        }
                        $kmds.= '期待能与您再次交易<br>发卡网技术支持 By:EDLM';
                        $mail->IsSMTP();
                        $mail->Host = 'smtp.qq.com';
                        $mail->Port = 465;
                        $mail->SMTPAuth = true;
                        $mail->SMTPSecure = "ssl"; 
                        $mail->CharSet = "UTF-8";
                        $mail->Encoding = "base64";
                        $mail->Username = $lp['mailu'];
                        $mail->Password = $lp['mailp'];
                        $mail->Subject = '来自'.$cfs['title'].'给您发送的卡密,感谢您的信任与支持,希望与您下次交易';
                        $mail->From = $lp['mailu'];
                        $mail->FromName = $cfs['title'];
                        $mail->AddAddress($s['pwd']);
                        $mail->IsHTML(true);
                        $mail->Body = $kmds;
                        $mail->Send();
                    }
                    return $this->JsAlert('补单成功','javascript:self.location=document.referrer;');
                }
                return $this->JsAlert('补单出错,请稍后重试','javascript:self.location=document.referrer;');
	    	}
	    	//
	    	$data['to'] = '点我可通过商品筛选';
	    	$data['order'] = Db::table('orders o')->whereNull('win_order')->field('o.*,(select title from lists where lists.id = o.spid) AS title');
	    	$tid = '';
	    	if(!empty($_GET['tid'])){
	    		$tid = (int)$_GET['tid'];
	    		$data['to'] = '当前：'.Db::table('lists')->where('id',(int)$_GET['tid'])->find()['title'];
	    		if($data['to']){
	    			$data['order'] = $data['order']->where('spid',(int)$_GET['tid']);
	    		}
	    	}
	    	if(!empty($_GET['order'])){
	    		$data['order'] = $data['order']->where(array(
	    			'order' => $_GET['order']
	    		));
	    	}
	    	//
	    	$data['lists'] = Db::table('lists')->order('or desc')->select();
	    	$data['order'] = $data['order']->order('time desc')->paginate(18,false,[
	    		'query' => [
	    			'tid' => $tid
	    		]
	    	]);
		    $data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function newkm()
	    {
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	//
	    	if(!empty($_POST['spid'])){
		        $newkms = explode("\n",$_POST['km']);
		        foreach ($newkms as $v){
		        	$jk = true;
		        	$km = str_replace("\r", '', $v);
		        	if($km){
		        		if(isset($_POST['lv'])){
		        			$s = Db::table('kms')->where(array(
		        				'spid' => (int)$_POST['spid'],
		        				'km' => $km
		        			))->limit(1)->select();
		        			if($s){
		        				$jk = false;
		        			}
		        		}
		        		if($jk){
		        			Db::table('kms')->insert([
		        				'km' => $km,
		        				'spid' => (int)$_POST['spid']
		        			]);
		        		}
		        	}
		        }
		        return $this->JsAlert('添加卡密完成','./newkm');
		    }
	    	//
	    	$data['lists'] = Db::table('lists')->select();
		    $data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function admin()
	    {
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	//
	    	if($_POST){
	    		$out = false;
	    		if(!empty($_POST['passwords'])){
	    			$out = true;
	    			Db::table('admin')->where('id',1)->update([
	    				'passwords' => MD5($_POST['passwords'])
	    			]);
	    		}
	    		if(!empty($_POST['apppwd'])){
	    			Db::table('admin')->where('id',1)->update([
	    				'apppwd' => $_POST['apppwd']
	    			]);
	    		}
	    		Db::table('admin')->where('id',1)->update([
	    			'username' => $_POST['username'],
	    			'mailu' => $_POST['qqmu'],
	    			'mailp' => $_POST['qqmp'],
	    			'appid' => $_POST['appid']
	    		]);
	    		if($out){
	    			return $this->JsAlert('保存完毕','./login?out');
	    		}
	    		return $this->JsAlert('保存完毕','');
	    	}
	    	//
	    	$data['admin'] = Db::table('admin')->find();
		    $data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function unsold()
	    {
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	$spid = '';
	    	//条件
	    	if(!empty($_GET['spid'])){
	    		$spid = (int)$_GET['spid'];
	    		$data['kmc'] = Db::table('kms')->where('spid',(int)$_GET['spid'])->whereNotNull('order')->count();
	    		$data['kml'] = Db::table('kms k')->where('spid',(int)$_GET['spid'])->whereNotNull('order')->field('k.*,(select title from lists where lists.id = k.spid) AS name')->order('time desc')->paginate(18,false,[
	    			'query'=>[
	    				'spid' => $spid
	    			]
	    		]);
	    	}else{
	    		$data['kmc'] = Db::table('kms k')->whereNotNull('order')->count();
	    		$data['kml'] = Db::table('kms k')->whereNotNull('order')->field('k.*,(select title from lists where lists.id = k.spid) AS name')->order('time desc')->paginate(18,false,[
	    			'query'=>[
	    				'spid' => $spid
	    			]
	    		]);
	    	}
	    	//
	    	$data['lists'] = Db::table('lists')->select();
		    $data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function sold()
	    {
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	//操作
	    	if(!empty($_GET['d'])){
	    		Db::table('kms')->where('id',(int)$_GET['d'])->delete();
	    		return $this->JsAlert('单个卡密删除操作执行完毕','./sold');
	    	}
	    	//
	    	if(!empty($_POST['de'])){
	    		if(!empty($_GET['spid'])){
		    		Db::table('kms')->where('spid',(int)$_GET['spid'])->whereNull('order')->delete();
		    	}else{
		    		Db::table('kms')->whereNull('order')->delete();
		    	}
		    	return $this->JsAlert('删除操作执行完毕','./sold');
	    	}else if(!empty($_POST['dd'])){
	    		if(!empty($_POST['kms'])){
	    			Db::table('kms')->whereIn('id',$_POST['kms'])->delete();
	    			return $this->JsAlert('卡密删除操作执行完毕','./sold');
	    		}
	    		return $this->JsAlert('请先选择需要删除的卡密','./sold');
	    	}
	    	$spid = '';
	    	//条件
	    	if(!empty($_GET['spid'])){
	    		$spid = (int)$_GET['spid'];
	    		$data['kmc'] = Db::table('kms')->where('spid',(int)$_GET['spid'])->whereNull('order')->count();
	    		$data['kml'] = Db::table('kms k')->where('spid',(int)$_GET['spid'])->whereNull('order')->field('k.*,(select title from lists where lists.id = k.spid) AS name')->order('time desc')->paginate(18,false,[
	    			'query'=>[
	    				'spid' => $spid
	    			]
	    		]);
	    	}else{
	    		$data['kmc'] = Db::table('kms k')->whereNull('order')->count();
	    		$data['kml'] = Db::table('kms k')->whereNull('order')->field('k.*,(select title from lists where lists.id = k.spid) AS name')->order('time desc')->paginate(18,false,[
	    			'query'=>[
	    				'spid' => $spid
	    			]
	    		]);
	    	}
	    	if(Session::has('cachefile')){
	    		unlink('download/'.Session::get('cachefile'));
	    		Session::delete('cachefile');
	    	}
	    	if(isset($_GET['dc'])){
	    		$result = '';
	    		foreach ($data['kml'] as $v){
		            $result .= $v['km']."\r\n";
		        }
		        $name = date("Ymdhis").rand(1,99999).'.txt';
		        file_put_contents('download/'.$name, $result);
		        header("Content-Type: application/force-download");
		        header("Content-Disposition: attachment; filename=$name");
		        Session::set('cachefile',$name);
		        return readfile('download/'.$name);
	    	}
	    	//
	    	$data['lists'] = Db::table('lists')->select();
		    $data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function sp()
	    {
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	//
	    	if(!empty($_GET['dl'])){
	    		Db::table('lists')->where('id',(int)$_GET['dl'])->delete();
	    		Db::table('kms')->where('spid',(int)$_GET['dl'])->whereNull('order')->delete();
	    		return $this->JsAlert('删除操作执行完毕','javascript:self.location=document.referrer;');
	    	}else if(!empty($_GET['ss'])){
	    		Db::table('lists')->where('id',(int)$_GET['ss'])->update([
	    			's' => 1
	    		]);
	    		return $this->JsAlert('修改状态成功','./sp');
	    	}else if(!empty($_GET['xx'])){
	    		Db::table('lists')->where('id',(int)$_GET['xx'])->update([
	    			's' => 0
	    		]);
	    		return $this->JsAlert('修改状态成功','./sp');
	    	}
	    	//
	    	if(!empty($_POST['new'])){
	    		$i = Db::table('lists')->insert([
	    			'title' => $_POST['title'],
	    			'mode' => base64_encode($_POST['mode']),
	    			'money' => $_POST['money'],
	    			'type' => (int)$_POST['type']
	    		]);
	    		if($i){
	    			return $this->JsAlert('新增成功','');
	    		}
	    		return $this->JsAlert('新增失败,请稍后重试','');
	    	}else if(!empty($_POST['edit']) and !empty($_POST['eid'])){
	    		if(!empty($_POST['type'])){
	    			$u = Db::table('lists')->where('id',(int)$_POST['eid'])->update([
		    			'title' => $_POST['title'],
		    			'mode' => base64_encode($_POST['mode']),
		    			'money' => $_POST['money'],
		    			'type' =>  (int)$_POST['type'],
		    			'or' => (int)$_POST['id']
		    		]);
	    		}else{
	    			$u = Db::table('lists')->where('id',(int)$_POST['eid'])->update([
		    			'title' => $_POST['title'],
		    			'mode' => base64_encode($_POST['mode']),
		    			'money' => $_POST['money'],
		    			'or' => (int)$_POST['id']
		    		]);
	    		}
	    		if($u){
	    			return $this->JsAlert('编辑成功','');
	    		}
	    		return $this->JsAlert('编辑失败,请稍后重试','');
	    	}
	    	$data['to'] = '点我可通过分类筛选';
	    	$data['lists'] = Db::table('lists l')->field('l.*,(select count(*) from kms where kms.spid = l.id and kms.order IS NULL) AS count,(select name from type where type.id = l.type) AS tn');
	    	$tid = '';
	    	if(!empty($_GET['tid'])){
	    		$tid = (int)$_GET['tid'];
	    		$data['to'] = '当前：'.Db::table('type')->where('id',(int)$_GET['tid'])->find()['name'];
	    		if($data['to']){
	    			$data['lists'] = $data['lists']->where('type',(int)$_GET['tid']);
	    		}
	    	}
	    	if(!empty($_POST['spn'])){
	    		$data['lists'] = $data['lists']->where(array(
	    			'title' => ['like','%'.$_POST['spn'].'%']
	    		));
	    	}
	    	//
	    	$data['types'] = Db::table('type')->order('or desc')->select();
	    	$data['lists'] = $data['lists']->order('or desc')->paginate(18,false,[
	    		'query' => [
	    			'tid' => $tid
	    		]
	    	]);
		    $data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function c(){
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	//
	    	if($_POST){
	    		$u = Db::table('config')->where('id',1)->update([
	    			'title' => $_POST['title'],
	    			'tail' => $_POST['tail'],
	    			'keywords' => $_POST['keywords'],
	    			'description' => $_POST['description'],
	    			'gg' => $_POST['gg'],
	    			'qq' => $_POST['qq'],
	    			'background' => $_POST['burl']
	    		]);
	    		if($u){
	    			return $this->JsAlert('保存成功','');
	    		}
	    		return $this->JsAlert('保存失败，请稍后重试','');
	    	}
	    	//
	    	$data['config'] = Db::table('config')->find(1);
	    	$data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function type(){
	    	$u = $this->auth();
	    	if($u['code']!=0){
	    		return $this->JsAlert('','login');
	    	}
	    	//
	    	if($_POST){
	    		if(isset($_POST['edit'])){
	    			$u = Db::table('type')->where(array('id'=>$_POST['eid']))->update([
	    				'name' => $_POST['name'],
	    				'or' => $_POST['id']
	    			]);
	    			if($u){
	    				return $this->JsAlert('修改成功','');
	    			}
	    			return $this->JsAlert('修改失败,请稍后重试','');
	    		}
	    		if(!empty($_POST['nt'])){
	    			$i = Db::table('type')->insert([
	    				'name' => $_POST['nt']
	    			]);
	    			if($i){
	    				return $this->JsAlert('新增成功','');
	    			}
	    			return $this->JsAlert('新增失败,请稍后重试','');
	    		}
	    	}
	    	if(!empty($_GET['dt'])){
	    		$s = Db::table('type')->where('id',(int)$_GET['dt'])->delete();
	    		if($s){
	    			return $this->JsAlert('删除成功','./type');
	    		}
	    		return $this->JsAlert('删除失败,请稍后重试','./type');
	    	}else if(!empty($_GET['ss'])){
	    		Db::table('type')->where('id',(int)$_GET['ss'])->update([
	    			's' => 1
	    		]);
	    		return $this->JsAlert('修改状态成功','./type');
	    	}else if(!empty($_GET['xx'])){
	    		Db::table('type')->where('id',(int)$_GET['xx'])->update([
	    			's' => 0
	    		]);
	    		return $this->JsAlert('修改状态成功','./type');
	    	}
	    	//
	    	$data['types'] = Db::table('type')->order('or desc')->paginate(20);
	    	$data['active'] = $this->a(input('mod'));
		    return view('',$data);
	    }
	    public function login(){
	    	if(isset($_GET['out'])){
	    		Session::delete('token');
	    		return $this->JsAlert('注销登陆成功','./login');
	    	}
	    	$u = $this->auth();
	    	if($u['code']===0){
	    		return $this->JsAlert('','index');
	    	}
	    	if(!empty($_POST['username']) and !empty($_POST['passwords'])){
	    		$s = Db::table('admin')->find(1);
	    		$pass = md5($_POST['passwords']);
	    		$user = $_POST['username'];
	    		if($s['username']!=$user or $s['passwords']!=$pass){
	    			return $this->JsAlert('用户名或密码错误，请勿非法操作！','');
	    		}
	    		$token = md5(time().$pass.'maddog');
	    		Session::set('token',$token);
	    		Db::table('admin')->where('id',1)->update([
	    			'token' => $token
	    		]);
	    		return $this->JsAlert('','index');
	    	}
	    	return view();
	    }
	}
