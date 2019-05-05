<?php
namespace app\index\controller;

class index 
{
    public function index()
    {
        //return 'hello :)';
        $array = array(
            'Server_1' => '127.0.0.1',
            'Server_1_port' => '28015',
            'Server_1_using' => false,
            'Server_2' => '',
            'Server_2_port' => '',
            'Server_2_using' => false,
            'Server_3' => '',
            'Server_3_port' => '',
            'Server_3_using' => false,
            'Server_4' => '',
            'Server_4_port' => '',
            'Server_4_using' => false,
            'Server_5' => '',
            'Server_5_port' => '',
            'Server_5_using' => false
        );
        return base64_encode(json_encode($array));
    }
    private function CheckPlayerKey()
    {
        $param = input('get.');
        $status = false;
        if(empty($param['secKey']))		
            die("unk_secKey");
        $has = db('userdata')->where('Key', $param['secKey'])->find();
        if(empty($has))	
            die("not_find_key");
        return $has['Key'];
    }
    public function logout()
    {
        $secKey = $this->CheckPlayerKey();
        db('userdata')->where('Key', $secKey)->setField('Online',0);
    }
    public function login()
    {
        $param = input('get.');
        $status = false;
        $secKey = "uFuck";
    	if(empty($param['logname']) || empty($param['logpass'])){		
            return json([
                'msgType' => "Login",
                'msg' => '',
                'username'=> "",
                'success'=> $status
            ]);
    	}
    	$has = db('userdata')->where('username', $param['logname'])->find();
    	if(!empty($has)){	
    		if($has['password'] != md5(md5($param['logpass'])))		
                $status = false;
            else
            {
                $status = true;
                $secKey = md5(base64_encode($has['username'].$has['email'].$has['password']));
            }
        }
        if($has['Online'] == 1)
        {
            if(time() - $has['LastActivityTime'] < 60)
            {
                return json([
                    'msgType' => "Login",
                    'username'=> $param['logname'],
                    'msg' => 'was_Online',
                    'secKey' => $secKey,
                    'success'=> false
                ]);
            }
        }
        db('userdata')->where('username', $param['logname'])->setField('Online',1);
        db('userdata')->where('Key', $secKey)->setField('LastActivityTime',time());
        return json([
            'msgType' => "Login",
            'username'=> $param['logname'],
            'msg' => '',
            'secKey' => $secKey,
            'success'=> $status
        ]);
    }
    public function register()
    {
        $param = input('get.');
        $status = false;
		if(empty($param['Regname']) || empty($param['Regpass']) || empty($param['Regemail'])){		
            return json([
                'msgType' => "register",
                'uFuck' => 1,
                'success'=> $status
            ]);
    	}
		$name = strip_tags($param['Regname']);
        $email = strip_tags($param['Regemail']);
        $tmp_name=explode("@",$email);
		if (filter_var($email) === false || checkdnsrr(array_pop($tmp_name),"MX") === false) {
            return json([
                'msgType' => "register",
                'uFuck' => 2,
                'success'=> $status
            ]);
		}
		
		$CheckName = db('userdata')->where('username', $name)->find();
		if(empty($CheckName) === false){	
            return json([
                'msgType' => "register",
                'uFuck' => 3,
                'success'=> $status
            ]);
		}
		$CheckEmail = db('userdata')->where('email', $email)->find();
		if(empty($CheckEmail) === false){	
            return json([
                'msgType' => "register",
                'uFuck' => 4,
                'success'=> $status
            ]);
		}
		//md5 ( md5 ( 让CMD5死掉的东西 ))
		$TheKey = md5(base64_encode($name.$email.md5(md5($param['Regpass']))));
		$data = [
            'username' => $name,
            'password' => md5(md5($param['Regpass'])),
            'email' =>  $email,
            'Key' => $TheKey
        ];
		$ok = db('userdata')->insert($data);
		if($ok)
		{
            $status = true;
            return json([
                'msgType' => "register",
                'uFuck' => 0,
                'success'=> $status
            ]);
        }
        return json([
            'msgType' => "register",
            'uFuck' => 5,
            'success'=> $status
        ]);
    }
    public function match_unready($secKey)
    {
        if(empty($secKey))
            $secKey = $this->CheckPlayerKey();
        $data = db('userdata')->where('Key', $secKey)->find();
        $usrRoomid = $data['roomid'];
        if($usrRoomid == '0')
        {
            return json([
                'MsgType' => "Match_unReady",
                'Result' => "Not_IN_ROOM",
                'RoomID'=> $usrRoomid,
                'success'=> false
            ]);
        }
        $Roomdata = db('roomlist')->where('RoomID', $usrRoomid)->find();
        if(empty($Roomdata))
        {
            return json([
                'MsgType' => "Match_unReady",
                'RoomID'=> $usrRoomid,
                'Result'=>'Wrong_Room_ID',
                'success'=> false
            ]);
        }
        $decode_array = json_decode(base64_decode($Roomdata['PlayerList']), true);
        $playernumber = array_search($data['username'],$decode_array,true);
        if($playernumber == false)  
        {
            return json([
                'MsgType' => "Match_unReady",
                'RoomID'=> $usrRoomid,
                'Result'=>'Wrong_Room_ID',
                'success'=> false
            ]);
        }
        if($playernumber == 'Player_1')
            $decode_array['Player_1_Ready'] = false;
        else if($playernumber == 'Player_2')
            $decode_array['Player_2_Ready'] = false;
        else if($playernumber == 'Player_3')
            $decode_array['Player_3_Ready'] = false;
        else if($playernumber == 'Player_4')
            $decode_array['Player_4_Ready'] = false;
        else if($playernumber == 'Player_5')
            $decode_array['Player_5_Ready'] = false;
        else
        {
            return json([
                'MsgType' => "Match_unReady",
                'RoomID'=> $usrRoomid,
                'Result'=>'unk_Error',
                'success'=> false
            ]);
        }
        db('roomlist')->where('RoomID', $usrRoomid)->setField('PlayerList', base64_encode(json_encode($decode_array)));
        db('userdata')->where('Key', $secKey)->setField('LastActivityTime',time());
        return json([
            'MsgType' => "Match_unReady",
            'RoomID'=> $usrRoomid,
            'Result'=>'Good',
            'success'=> true
        ]);
    }
    /*
    $searchRank = 搜索时间,最大不超过1000
    搜索步骤:
    1. 判断冷却时间
    2. 排序rnak
    3. 寻找分值最接近的队伍
    4. 判断两边人数,少了多少人
    5. 从其他最接近的人数队伍中抽取填补
    6. 完成匹配
    */
    private function match_search($secKey,$data,$usrRoomid,$Roomdata,$decode_array,$searchRank)
    {
        $searchDB = db('searchlist');
        $my = $searchDB->where('RoomID', $usrRoomid)->find();
        $ent = $my;
        if(empty($my))
            return false;

        //1.判断冷却时间
        if(time() - $my['CDTime'] < 2)
            return false;
        //检查服务器
        
        $serverIP = $this->match_DistributServer(false);
        if($serverIP == false)
           return false;
        db('searchlist')->where('RoomID', $usrRoomid)->setField('CDTime',time());
        //2.排序rank
        if($searchRank < 0 || $searchRank > 1500)
            $searchRank = 1500;
        $rank_DESC = $searchDB->order('Rank','DESC')->select();
        $num = count($rank_DESC);
        //3.寻找分值最接近的队伍
        $found = false;
        for($i=0;$i<$num;$i++){
            if($rank_DESC[$i]['RoomID'] == $usrRoomid)
                continue;
            if(abs($my['Rank'] - $rank_DESC[$i]['Rank']) <= $searchRank)
            {
                $found = true;
                $ent = $rank_DESC[$i];
                break;
            }
        }
        if(!$found)
            return false;
        $team1_array = array(
            'Player_1' => $decode_array['Player_1'],
            'Player_2' => $decode_array['Player_2'],
            'Player_3' => $decode_array['Player_3'],
            'Player_4' => $decode_array['Player_4'],
            'Player_5' => $decode_array['Player_5']
        );
        $ent_decode_array = json_decode(base64_decode($ent['PlayerList']), true);
        $team2_array = array(
            'Player_1' => $ent_decode_array['Player_1'],
            'Player_2' => $ent_decode_array['Player_2'],
            'Player_3' => $ent_decode_array['Player_3'],
            'Player_4' => $ent_decode_array['Player_4'],
            'Player_5' => $ent_decode_array['Player_5']
        );
      //  $userDB = db('userdata');
        //4.判断两边人数 从其他最接近的人数队伍中抽取填补
        if($my['PlayerNumber'] != 5)
        {     
            //寻找少了多少个玩家
            $needPlayers = abs($my['PlayerNumber'] - 5);
            for($i=0;$i<$num;$i++){
                //已经加进去的就不能再加了
                if($rank_DESC[$i]['RoomID'] == $usrRoomid || $rank_DESC[$i] == $ent['RoomID'])
                    continue;   
                $tmp_decodearray = json_decode(base64_decode($rank_DESC[$i]['PlayerList']), true);
                $goshit = false;
                for ($z=1; $z<=5; $z++) {
                    $tmp_name = 'Player_'.strval($z);
                    if($tmp_decodearray[$tmp_name] != '')
                    {
                        if(array_search($tmp_decodearray[$tmp_name],$team2_array,true) !== false
                        || array_search($tmp_decodearray[$tmp_name],$team1_array,true) !== false
                       // || $userDB->where('username',$tmp_decodearray[$tmp_name])->find()['matching'] != '0'
                        )
                        {
                            $goshit = true;
                            break;
                        }
                    }
                }
                if($goshit)
                    continue;
                if($rank_DESC[$i]['RoomID'] == $usrRoomid || $rank_DESC[$i] == $ent['RoomID'])
                    continue;
                //如果这个团队的人数大于了我们需要的人数
                if($rank_DESC[$i]['PlayerNumber'] > $needPlayers)
                    continue;
                //等于了,直接加上去咯
                if($rank_DESC[$i]['PlayerNumber'] == $needPlayers)
                {
                    $ent_decode = json_decode(base64_decode($rank_DESC[$i]['PlayerList']), true);
                    for ($x=1; $x<=5; $x++) {
                        $name = 'Player_'.strval($x);
                        if($team1_array[$name] == '')
                        {
                            for ($z=1; $z<=5; $z++) {
                                $ent_name = 'Player_'.strval($z);
                                if($ent_decode[$ent_name] != '')
                                {
                                    if(array_search($ent_decode[$ent_name],$team1_array,true) == false)
                                    {
                                        $team1_array[$name] = $ent_decode[$ent_name];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    break;
                }
                //小于
                if($rank_DESC[$i]['PlayerNumber'] < $needPlayers)
                {
                    $ent_decode = json_decode(base64_decode($rank_DESC[$i]['PlayerList']), true);
                    for ($x=1; $x<=5; $x++) {
                        $name = 'Player_'.strval($x);
                        if($team1_array[$name] == '')
                        {
                            for ($z=1; $z<=5; $z++) {
                                $ent_name = 'Player_'.strval($z);
                                if($ent_decode[$ent_name] != '')
                                {
                                    if(array_search($ent_decode[$ent_name],$team1_array,true) == false)
                                    {
                                        $team1_array[$name] = $ent_decode[$ent_name];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    //填完了,还不够,寻找下一个
                    $needPlayers = abs(($rank_DESC[$i]['PlayerNumber'] + $my['PlayerNumber']) - 5);
                    continue;
                }
            }
        }
        if($ent['PlayerNumber'] != 5)
        { 
            $needPlayers = abs($ent['PlayerNumber'] - 5);
            for($i=0;$i<$num;$i++){
                if($rank_DESC[$i]['RoomID'] == $usrRoomid || $rank_DESC[$i]['RoomID'] == $ent['RoomID'])
                    continue;
                $tmp_decodearray = json_decode(base64_decode($rank_DESC[$i]['PlayerList']), true);
                $goshit = false;
                for ($z=1; $z<=5; $z++) {
                    $tmp_name = 'Player_'.strval($z);
                    if($tmp_decodearray[$tmp_name] != '')
                    {
                        if(array_search($tmp_decodearray[$tmp_name],$team2_array,true) !== false
                        || array_search($tmp_decodearray[$tmp_name],$team1_array,true) !== false
                       // || $userDB->where('username',$tmp_decodearray[$tmp_name])->find()['matching'] != '0'
                        )
                        {
                            $goshit = true;
                            break;
                        }
                    }
                }
                if($goshit)
                    continue;
                if($rank_DESC[$i]['PlayerNumber'] > $needPlayers)
                    continue;
                if($rank_DESC[$i]['PlayerNumber'] == $needPlayers)
                {
                    $ent_decode = json_decode(base64_decode($rank_DESC[$i]['PlayerList']), true);
                    for ($x=1; $x<=5; $x++) {
                        $name = 'Player_'.strval($x);
                        if($team2_array[$name] == '')
                        {
                            for ($z=1; $z<=5; $z++) {
                                $ent_name = 'Player_'.strval($z);
                                if($ent_decode[$ent_name] != '')
                                {
                                    if(array_search($ent_decode[$ent_name],$team2_array,true) == false)
                                    {
                                        $team2_array[$name] = $ent_decode[$ent_name];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    break;
                }
                if($rank_DESC[$i]['PlayerNumber'] < $needPlayers)
                {
                    $ent_decode = json_decode(base64_decode($rank_DESC[$i]['PlayerList']), true);
                    for ($x=1; $x<=5; $x++) {
                        $name = 'Player_'.strval($x);
                        if($team2_array[$name] == '')
                        {
                            for ($z=1; $z<=5; $z++) {
                                $ent_name = 'Player_'.strval($z);
                                if($ent_decode[$ent_name] != '')
                                {
                                    if(array_search($ent_decode[$ent_name],$team2_array,true) == false)
                                    {
                                        $team2_array[$name] = $ent_decode[$ent_name];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    $needPlayers = abs(($rank_DESC[$i]['PlayerNumber'] + $ent['PlayerNumber']) - 5);
                    continue;
                }
            }
        }
        
        //最后一遍检查
        $last_check_good = true;
        for ($x=1; $x<=5; $x++) {
            $name = 'Player_'.strval($x);
            if($team1_array[$name] == '' || $team2_array[$name] == '')
                $last_check_good = false;
        }
        if(!$last_check_good)
            return false;
        $serverIP = $this->match_DistributServer(true);
        if($serverIP == false)
            return false;
        //完成匹配:
        $matchID = strval(time()).md5(time() + $usrRoomid + $ent['RoomID'],FALSE);
        //$shit = array_merge($team1_array,$team2_array);
        $shit = $team1_array;
        $userDB = db('userdata');
        $forI = 1;

        for ($x=1; $x<=10; $x++) {
            $forI = $x;
            if($x >= 6)
            {
                $forI = $x - 5;
                $shit = $team2_array;
            }            
            $name = 'Player_'.strval($forI);
            db('userdata')->where('username', $shit[$name])->setField('matching',$matchID);     
            $roomID =  $userDB->where('username', $shit[$name])->find()['roomid'];
            db('roomlist')->where('RoomID', $roomID)->setField('ingame',1);
            db('roomlist')->where('RoomID', $roomID)->setField('SearchTime',0);
            $this->match_unready($userDB->where('username', $shit[$name])->find()['Key']);
            if(empty(db('searchmsglist')->where('RoomID',$roomID)->find()) == false)
                db('searchmsglist')->where('RoomID',$roomID)->delete();
            if(empty(db('searchlist')->where('RoomID',$roomID)->find()) == false)
                db('searchlist')->where('RoomID',$roomID)->delete();
            if(empty(db('searchmsglist')->where('RoomID',$roomID)->find()) == false)
                db('searchmsglist')->where('RoomID',$roomID)->delete();
        }
        db('match')->insert([
            'MatchID' => $matchID,
            'HostRoomID'=> $my['RoomID'],
            'Team_1' => base64_encode(json_encode($team1_array)),
            'Team_2' => base64_encode(json_encode($team2_array)),
            'Team1_Score' => 0,
            'Team2_Score' => 0,
            'HVH' => 0,
            'Server'=> $serverIP
        ]);
        return true;
    }
    
    //不知道有没有必要使用
    private function match_fuck_msg($secKey,$data,$usrRoomid,$Roomdata,$decode_array)
    {
        $DB = db('searchmsglist');
        //如果房间没有在搜索队列中
        if(empty($DB->where('RoomID', $usrRoomid)->find()))
        {
            //增加
            db('searchmsglist')->insert([
                'RoomID' => $usrRoomid,
                'Time' => time()
            ]);
            return false;
        }else{
            //如果在搜索队列中
            $MSG = $DB->order('Time','ASC')->select();
            if($Roomdata['SearchTime'] % 10 == 0)
                db('searchmsglist')->where('RoomID',$Roomdata['RoomID'])->setField("Time",time());
            //判断第一个房间号是否是自己的房间号
            if($MSG[0]['RoomID'] == $Roomdata['RoomID'])
            {
                //开始搜索...
                return true;
            }
            else
            {
                $userDB = db('userdata')->where('roomid', $MSG[0]['RoomID'])->find();       
                if(time() - $userDB['LastActivityTime'] > 10)
                    $this->room_kick($userDB['username'],"Room_TimeOut");
                /*
                if(time() - db('userdata')->where('roomid', $MSG[0]['RoomID'])->find()['LastActivityTime'] > 10)
                {
                    if(empty(db('searchlist')->where('RoomID',$usrRoomid)->find()) == false)
                        db('searchlist')->where('RoomID',$usrRoomid)->delete();
                    if(empty(db('searchmsglist')->where('RoomID',$usrRoomid)->find()) == false)
                        db('searchmsglist')->where('RoomID',$usrRoomid)->delete();
                }*/
                return false;
            }
                
        }
    }
    private function match_addlist($secKey,$data,$usrRoomid,$Roomdata,$decode_array)
    {
        if($Roomdata['ReadyNumber'] < $Roomdata['PlayerNumber'])
            return false;
        if(empty(db('searchlist')->where('RoomID', $usrRoomid)->find()))
        {
            $json = json_encode([
                'Player_1' => $decode_array['Player_1'],
                'Player_2' => $decode_array['Player_2'],
                'Player_3' => $decode_array['Player_3'],
                'Player_4' => $decode_array['Player_4'],
                'Player_5' => $decode_array['Player_5']
            ]);
            $MaxRank = 0;
            for ($x=1; $x<=5; $x++) {
                $rank = 'Player_'.strval($x).'_Rank';
                if($decode_array[$rank] != 0)
                {
                    if($decode_array[$rank] > $MaxRank)
                        $MaxRank = $decode_array[$rank];
                }
            }
            db('searchlist')->insert([
                'RoomID' => $usrRoomid,
                'PlayerList' => base64_encode($json),
                'Rank' => $MaxRank,
                'PlayerNumber' => $Roomdata['PlayerNumber'],
                'Time' => time(),
                'CDTime' => time()
            ]);
        }
        else
        {
            db('searchlist')->where('RoomID', $usrRoomid)->setField('Time',time());
        }
        return true;
    }
    /*
    1.排序服务器最多的集群优先
    2.检查每个服务器有在线而且不使用的
    3.有在线而且不使用的,分配进去
    */
    private function match_DistributServer($set)
    {
       
        //1.排序服务器最多的集群优先
        $DB = db('match_servers');
        $Matchserver = db('match_servers');
        $servers_DESC = $Matchserver->order('Numbers','DESC')->select();
        $num = count($servers_DESC);
        //2.检查每个服务器有在线而且不使用的
        for($i=0;$i<$num;$i++){
            
            $decode_array = json_decode(base64_decode($servers_DESC[$i]['MatchServers']), true);
            for($z=1;$z <= $servers_DESC[$i]['Numbers'];$z++)
            {
                $name = 'Server_'.strval($z);
                $port = 'Server_'.strval($z).'_port';
                $using = 'Server_'.strval($z).'_using';
                //3.有在线而且不使用的,分配进去
                if($decode_array[$using] == false && $set == true)
                {       
                    $decode_array[$using] = true;
                    db('match_servers')->where('ID',$servers_DESC[$i]['ID'])->setField('MatchServers',base64_encode(json_encode($decode_array)));
                    return $decode_array[$name];
                }else if($decode_array[$using] == false)
                {
                    return $decode_array[$name];
                }
            }
        }
        return false;
    }
    public function match_ready()
    {
        $secKey = $this->CheckPlayerKey();
        $data = db('userdata')->where('Key', $secKey)->find();
        $usrRoomid = $data['roomid'];
        if($usrRoomid == '0')
        {
            return json([
                'MsgType' => "Match_Ready",
                'Result' => "Not_IN_ROOM",
                'RoomID'=> $usrRoomid,
                'success'=> false
            ]);
        }
        $Roomdata = db('roomlist')->where('RoomID', $usrRoomid)->find();
        if(empty($Roomdata))
        {
            return json([
                'MsgType' => "Match_Ready",
                'RoomID'=> $usrRoomid,
                'Result'=>'Wrong_Room_ID',
                'success'=> false
            ]);
        }
        $decode_array = json_decode(base64_decode($Roomdata['PlayerList']), true);
        $playernumber = array_search($data['username'],$decode_array,true);
        if($playernumber == false)  
        {
            return json([
                'MsgType' => "Match_Ready",
                'RoomID'=> $usrRoomid,
                'Result'=>'Wrong_Room_ID',
                'success'=> false
            ]);
        }
        if($playernumber == 'Player_1')
            $decode_array['Player_1_Ready'] = true;
        else if($playernumber == 'Player_2')
            $decode_array['Player_2_Ready'] = true;
        else if($playernumber == 'Player_3')
            $decode_array['Player_3_Ready'] = true;
        else if($playernumber == 'Player_4')
            $decode_array['Player_4_Ready'] = true;
        else if($playernumber == 'Player_5')
            $decode_array['Player_5_Ready'] = true;
        else
        {
            return json([
                'MsgType' => "Match_Ready",
                'RoomID'=> $usrRoomid,
                'Result'=>'unk_Error',
                'success'=> false
            ]);
        }
        db('roomlist')->where('RoomID', $usrRoomid)->setField('PlayerList', base64_encode(json_encode($decode_array)));
        db('userdata')->where('Key', $secKey)->setField('LastActivityTime',time());
        return json([
            'MsgType' => "Match_Ready",
            'RoomID'=> $usrRoomid,
            'Result'=>'Good',
            'success'=> true
        ]);
    }
    public function room_check_in_room()
    {
        $secKey = $this->CheckPlayerKey();
        $data = db('userdata');
        $usrRoomid = $data->where('Key', $secKey)->find()['roomid'];
        db('userdata')->where('Key', $secKey)->setField('LastActivityTime',time());
        if($usrRoomid != '0')
        {
            if(empty(db('searchlist')->where('RoomID', $usrRoomid)->find()) == false)
            {
                if(empty(db('searchmsglist')->where('RoomID', $usrRoomid)->find()) == false)
                    db('searchmsglist')->where('RoomID', $usrRoomid)->delete();
                db('searchlist')->where('RoomID', $usrRoomid)->delete();
            }
            return json([
                'MsgType' => "Check_Room",
                'Result' => "in_room",
                'RoomID'=> $usrRoomid
            ]);
        }
        return json([
            'MsgType' => "Check_Room",
            'Result' => "no_room",
            'RoomID'=> $usrRoomid
        ]);
    }
    //serialize / unserialize
    public function room_create()
    {
        $secKey = $this->CheckPlayerKey();
        $data = db('userdata')->where('Key', $secKey)->find();
        $usrRoomid = $data['roomid'];
        if($usrRoomid != '0')
        {
            return json([
                'MsgType' => "CreateRoom",
                'Result' => "in_room",
                'RoomID'=> $usrRoomid,
                'success'=> false
            ]);
        }
        $room = substr(md5(time() + $data['username']),0,6);
        $json =  json_encode([
            'Player_1' => $data['username'],
            'Player_1_Ready' => false,
            'Player_1_Rank' => $data['rank'],
            'Player_2' => '',
            'Player_2_Ready' => false,
            'Player_2_Rank' => 0,
            'Player_3' => '',
            'Player_3_Ready' => false,
            'Player_3_Rank' => 0,
            'Player_4' => '',
            'Player_4_Ready' => false,
            'Player_4_Rank' => 0,
            'Player_5' => '',
            'Player_5_Ready' => false,
            'Player_5_Rank' => 0
        ]);
        db('userdata')->where('Key', $secKey)->setField('LastActivityTime',time());
        db('userdata')->where('key', $secKey)->setField('roomid',$room);		
        db('roomlist')->insert([
            'RoomID' => $room,
            'PlayerList' => base64_encode($json),
            'StartSearch' => 0,
            'SearchTime' => 0,
            'ingame' => 0,
            'PlayerNumber'=> 1,
            'ReadyNumber' => 0,
            'LastupdateTime' => time()
            ]);
        return json([
            'MsgType' => "CreateRoom",
            'RoomID'=> $room,
            'success'=> true
        ]);
    }
    public function room_join()
    {
        $secKey = $this->CheckPlayerKey();
        $data = db('userdata')->where('Key', $secKey)->find();
      
        $param = input('get.');
        if(empty($param['roomid']))
        {
            return json([
                'MsgType' => "JoinRoom",
                'RoomID'=> "NULL",
                'Result'=>'emptyRoom',
                'success'=> false
            ]);
        }
        if($data['roomid'] != "0")
        {
            return json([
                'MsgType' => "JoinRoom",
                'RoomID'=> $param['roomid'],
                'Result'=>'too_Much_Room',
                'success'=> false
            ]);
        }
        $Roomdata = db('roomlist')->where('RoomID', $param['roomid'])->find();
        if(empty($Roomdata))
        {
            return json([
                'MsgType' => "JoinRoom",
                'RoomID'=> $param['roomid'],
                'Result'=>'Wrong_Room_ID',
                'success'=> false
            ]);
        }
        if($Roomdata['PlayerNumber'] >= 5)
        {
            return json([
                'MsgType' => "JoinRoom",
                'RoomID'=> $param['roomid'],
                'Result'=>'Room_full',
                'success'=> false
            ]);
        }
        $decode_array = json_decode(base64_decode($Roomdata['PlayerList']), true);
        //var_dump($decode_array);
        if(array_search($data['username'],$decode_array,true) !== false)  
        {
            return json([
                'MsgType' => "JoinRoom",
                'RoomID'=> $param['roomid'],
                'Result'=>'Was_inRoom',
                'success'=> false
            ]);
        }
        //对不起,老夫能力不足,自己优化去 :)
        if($decode_array['Player_1'] == '')
        {
            $decode_array['Player_1'] = $data['username'];
            $decode_array['Player_1_Rank'] = $data['rank'];
            $decode_array['Player_1_Ready'] = false;
        }else
        if($decode_array['Player_2'] == '')
        {
            $decode_array['Player_2'] = $data['username'];
            $decode_array['Player_2_Rank'] = $data['rank'];
            $decode_array['Player_2_Ready'] = false;
        } else 
        if($decode_array['Player_3'] == '')
        {
            $decode_array['Player_3'] = $data['username'];
            $decode_array['Player_3_Rank'] = $data['rank'];
            $decode_array['Player_3_Ready'] = false;
        } else
        if($decode_array['Player_4'] == '')
        {
            $decode_array['Player_4'] = $data['username'];
            $decode_array['Player_4_Rank'] = $data['rank'];
            $decode_array['Player_4_Ready'] = false;
        } else
        if($decode_array['Player_5'] == '')
        {
            $decode_array['Player_5'] = $data['username'];
            $decode_array['Player_5_Rank'] = $data['rank'];
            $decode_array['Player_5_Ready'] = false;
        }else
        {
            return json([
                'MsgType' => "JoinRoom",
                'RoomID'=> $param['roomid'],
                'Result'=>'Unk_Error',
                'success'=> false
            ]);
        }
        $new_playernumber = intval($Roomdata['PlayerNumber']) + 1;
        db('userdata')->where('Key', $secKey)->setField('LastActivityTime',time() + 5);
        db('userdata')->where('key',$secKey)->setField('roomid',$param['roomid']);
        db('roomlist')->where('RoomID', $param['roomid'])->setField('PlayerList', base64_encode(json_encode($decode_array)));
        db('roomlist')->where('RoomID', $param['roomid'])->setField('PlayerNumber',$new_playernumber);
        return json([
            'MsgType' => "JoinRoom",
            'RoomID'=> $param['roomid'],
            'Result'=>'good',
            'success'=> true
        ]);
    }
   
    private function room_kick($Name,$MsgType)
    {
        $data = db('userdata')->where('username', $Name)->find();
      
        if(empty($data))
        {
            return json([
                'MsgType' => $MsgType,
                'RoomID'=> 'Null',
                'Result'=>'Wrong_RoomID',
                'success'=> false
            ]);
        }
        $usrRoomid = $data['roomid'];
        if($usrRoomid != '0')
        {
            $Roomdata = db('roomlist')->where('RoomID', $usrRoomid)->find();
            $decode_array = json_decode(base64_decode($Roomdata['PlayerList']), true);
            db('userdata')->where('key',$data['Key'])->setField('roomid',"0");
            if(array_search($data['username'],$decode_array,true) == false)  
            {
                return json([
                    'MsgType' => $MsgType,
                    'RoomID'=> $usrRoomid,
                    'Result'=>'Wrong_RoomID',
                    'success'=> false
                ]);
            }
            $playernumber = array_search($data['username'],$decode_array,true);
            $decode_array[$playernumber] = '';
            $decode_array[$playernumber + "_Ready"] = false;
            $decode_array[$playernumber + "_Rank"] = 0;
            $new_playernumber = intval($Roomdata['PlayerNumber']) - 1;
            $new_ReadyNumber = intval($Roomdata['PlayerNumber']) - 1;
            if($new_playernumber <= 0)
            {
                db('roomlist')->where('RoomID',$usrRoomid)->delete();
            }
            else
            {
                db('roomlist')->where('RoomID', $usrRoomid)->setField('PlayerList', base64_encode(json_encode($decode_array)));
                db('roomlist')->where('RoomID', $usrRoomid)->setField('PlayerNumber',$new_playernumber);
                db('roomlist')->where('RoomID', $usrRoomid)->setField('ReadyNumber',$new_ReadyNumber);
            }
            
            //if(empty(db('searchlist')->where('RoomID', $usrRoomid)->find()) == false)
            //    db('searchlist')->where('RoomID', $usrRoomid)->delete();
            if(empty(db('searchlist')->where('RoomID',$usrRoomid)->find()) == false)
                db('searchlist')->where('RoomID',$usrRoomid)->delete();
            if(empty(db('searchmsglist')->where('RoomID',$usrRoomid)->find()) == false)
                db('searchmsglist')->where('RoomID',$usrRoomid)->delete();

            return json([
                'MsgType' => $MsgType,
                'RoomID'=> $usrRoomid,
                'success'=> true
            ]);
        }
        return json([
            'MsgType' => $MsgType,
            'RoomID'=> $usrRoomid,
            'Result'=>'no_in_room',
            'success'=> false
        ]);
    }

    public function room_exit()
    {
        $secKey = $this->CheckPlayerKey();
        $data = db('userdata')->where('Key', $secKey)->find();
        db('userdata')->where('Key', $secKey)->setField('LastActivityTime',time());
        return $this->room_kick($data['username'],"ExitRoom");
        /*
        $usrRoomid = $data['roomid'];
        if($usrRoomid != '0')
        {
            $Roomdata = db('roomlist')->where('RoomID', $usrRoomid)->find();
            $decode_array = json_decode(base64_decode($Roomdata['PlayerList']), true);
            if(array_search($data['username'],$decode_array,true) == false)  
            {
                return json([
                    'MsgType' => "ExitRoom",
                    'RoomID'=> $usrRoomid,
                    'Result'=>'Wrong_RoomID',
                    'success'=> false
                ]);
            }
            $playernumber = array_search($data['username'],$decode_array,true);
            $decode_array[$playernumber] = '';
            $decode_array[$playernumber + "_Ready"] = false;
            $decode_array[$playernumber + "_Rank"] = 0;
            $new_playernumber = intval($Roomdata['PlayerNumber']) - 1;
            if($new_playernumber <= 0)
            {
                db('roomlist')->where('RoomID',$usrRoomid)->delete();
            }
            else
            {
                db('roomlist')->where('RoomID', $usrRoomid)->setField('PlayerList', base64_encode(json_encode($decode_array)));
                db('roomlist')->where('RoomID', $usrRoomid)->setField('PlayerNumber',$new_playernumber);
            }
            db('userdata')->where('key',$secKey)->setField('roomid',"0");
            return json([
                'MsgType' => "ExitRoom",
                'RoomID'=> $usrRoomid,
                'success'=> true
            ]);
        } 
        return json([
            'MsgType' => "ExitRoom",
            'RoomID'=> $usrRoomid,
            'Result'=>'no_in_room',
            'success'=> false
        ]);*/
    }
    public function match_sync()
    {
        $secKey = $this->CheckPlayerKey();
        $param = input('get.');
        if(empty($param['roomid']))
        {
            return json([
                'MsgType' => "Match_Sync",
                'RoomID'=> "NULL",
                'Result'=>'Room_Not_Found',
                'success'=> false
            ]);
        }
        $userDB = db('userdata');
        $data = $userDB->where('Key', $secKey)->find();
        $Roomdata = db('roomlist')->where('RoomID', $param['roomid'])->find();
        if(empty($Roomdata))
        {
            return json([
                'MsgType' => "Match_Sync",
                'RoomID'=> $param['roomid'],
                'Result'=>'ID_Not_Found',
                'success'=> false
            ]);
        }
        $decode_array = json_decode(base64_decode($Roomdata['PlayerList']), true);
        if(array_search($data['username'],$decode_array,true) == false)  
        {
            return json([
                'MsgType' => "Match_Sync",
                'RoomID'=> $param['roomid'],
                'Result'=>'Player_No_inRoom',
                'success'=> false
            ]);
        }
        db('userdata')->where('Key', $secKey)->setField('LastActivityTime',time());
        $LastRoomTime = $Roomdata['LastupdateTime'];
        if($Roomdata['ReadyNumber'] >= $Roomdata['PlayerNumber'])
        {
            if(time() - $LastRoomTime >= 1)
                db('roomlist')->where('RoomID', $param['roomid'])->setField('SearchTime',($Roomdata['SearchTime'] + 1));

            if($this->match_addlist($secKey,$data,$param['roomid'],$Roomdata,$decode_array))
            {
                if($this->match_fuck_msg($secKey,$data,$param['roomid'],$Roomdata,$decode_array))
                {
                    if($this->match_search($secKey,$data,$param['roomid'],$Roomdata,$decode_array,(100 * $Roomdata['SearchTime'])))
                    {
                        return json([
                            'MsgType' => "Match_Sync",
                            'RoomID'=> $myMtach['MatchID'],
                            'Result'=> 'GO_MATCH',
                            'Time'=> $Roomdata['SearchTime'],
                            'success'=> true
                        ]);
                    }
                }
                return json([
                    'MsgType' => "Match_Sync",
                    'RoomID'=> $param['roomid'],
                    'Result'=> 'Go_Search',
                    'Time'=> $Roomdata['SearchTime'],
                    'success'=> true
                ]);
            }else
            {
                return json([
                    'MsgType' => "Match_Sync",
                    'RoomID'=> $param['roomid'],
                    'Result'=> 'unk_Error',
                    'Time'=> $Roomdata['SearchTime'],
                    'success'=> false
                ]);
            }
        }else{
            if(empty(db('searchlist')->where('RoomID', $param['roomid'])->find()) == false)
            {
                db('searchlist')->where('RoomID', $param['roomid'])->delete();
                db('searchmsglist')->where('RoomID', $param['roomid'])->delete();     
                /*
                //设置其他人的准备状态为0    
                for ($x=1; $x<=5; $x++) {
                    $name = 'Player_'.strval($x);
                    if($decode_array[$name] != '')
                    {
                        $this->match_unready($userDB->where('username', $decode_array[$name])->find()['Key']);
                    }
                }*/
                return json([
                    'MsgType' => "Match_Sync",
                    'RoomID'=> $param['roomid'],
                    'Result'=> 'Stop_Search',
                    'Time'=> $Roomdata['SearchTime'],
                    'success'=> true
                ]);
            }
        }
        if($Roomdata['SearchTime'] > 0)
            db('roomlist')->where('RoomID', $param['roomid'])->setField('SearchTime',0);
        if($Roomdata['ingame'] == 1)
        {
            $myMtach = db('match')->where('HostRoomID', $param['roomid'])->find();
            return json([
                'MsgType' => "Match_Sync",
                'RoomID'=> $myMtach['MatchID'],
                'Result'=> 'IN_MATCH',
                'Time'=> $Roomdata['SearchTime'],
                'success'=> true
            ]);
        }
        return json([
            'MsgType' => "Match_Sync",
            'RoomID'=> $param['roomid'],
            'Result'=> 'Nothing',
            'Time'=> $Roomdata['SearchTime'],
            'success'=> true
        ]);
    }
    public function room_updatestatus()
    {
        $secKey = $this->CheckPlayerKey();
        $param = input('get.');
        if(empty($param['roomid']))
        {
            return json([
                'MsgType' => "RoomUpdate",
                'RoomID'=> "NULL",
                'Result'=>'Room_Not_Found',
                'success'=> false
            ]);
        }
        $userDB = db('userdata');
        $data = $userDB->where('Key', $secKey)->find();
        $Roomdata = db('roomlist')->where('RoomID', $param['roomid'])->find();
        if(empty($Roomdata))
        {
            return json([
                'MsgType' => "RoomUpdate",
                'RoomID'=> $param['roomid'],
                'Result'=>'ID_Not_Found',
                'success'=> false
            ]);
        }
        $decode_array = json_decode(base64_decode($Roomdata['PlayerList']), true);
        
        if(array_search($data['username'],$decode_array,true) == false)  
        {
            return json([
                'MsgType' => "RoomUpdate",
                'RoomID'=> $param['roomid'],
                'Result'=>'Player_No_inRoom',
                'success'=> false
            ]);
        }
        db('userdata')->where('Key', $secKey)->setField('LastActivityTime',time());

        $readyNumber = 0;
        $time = time();
        $LastRoomTime = $Roomdata['LastupdateTime'];
        $waslive = false;
        //2秒缓解服务器压力,1秒也可以
        if($time - $LastRoomTime >= 1)
        {     
            for ($x=1; $x<=5; $x++) {
                $name = 'Player_'.strval($x);
                if($decode_array[$name] != '')
                {
                    if($time - $userDB->where('username', $decode_array[$name])->find()['LastActivityTime'] > 3)
                    {
                        $this->room_kick($decode_array[$name],"Room_TimeOut");
                        $decode_array[$name] = '';
                        $waslive = true;
                    }
                }
                $name_ready = 'Player_'.strval($x).'_Ready';
                if($decode_array[$name_ready] && $decode_array[$name] != '')
                    $readyNumber = $readyNumber + 1;
            } 
            db('roomlist')->where('RoomID', $param['roomid'])->setField('ReadyNumber',$readyNumber);
            db('roomlist')->where('RoomID', $param['roomid'])->setField('LastupdateTime',time());
        }
        return json([
            'MsgType' => "RoomUpdate",
            'RoomID'=> $param['roomid'],
            'Result'=> base64_decode($Roomdata['PlayerList']),
            'success'=> true
        ]);
    }
}