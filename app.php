<?php if(! defined('APPPATH')) {
    header('location: index.php');
}

/**
 * This file is core of Tito-CMS.
 *
 * e-project-tech.com <harjito@mail.unnes.ac.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 /**
 * Class app
 */

class app{

    //--------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param array  $config
	 *
	 * @throws HTTPException
	 */

    function __construct(array $config){
        session_start();
        $this->session = $_SESSION[$config['session_name']];
        $this->method = $_SERVER["REQUEST_METHOD"];
        $query_string = explode('&',str_replace("r=","",$_SERVER['QUERY_STRING']));
        $this->query_string = $query_string[0];
        $this->username = '';
        $this->password = ''; 
        $this->message = '';
        $this->draft = "";
        $this->post = [];
        $this->config = $config;
    }

    public function run(){
        if($this->query_string==""){
            if(!$this->session){
                return $this->login();
            }
            return $this->dashboard();
        }
        if(method_exists($this, $this->query_string)){
            return $this->{$this->query_string}();
        }
        echo sprintf("Methode %s not found.", $this->query_string);
    }

    public function install($username, $password){
        $this->view(sprintf($this->loadView('install'), $username, $password));
    }

    public function login(){
        if($this->session){
            return $this->dashboard();
        }
        if($this->method === 'POST'){
            $this->username = $_POST['username'] ?? '';
            $this->password = $_POST['password'] ?? '';
            if($this->username === '' || $this->password === ''){
                return $this->formLogin();
            }
            $user = sprintf($this->config['path']['auth'].'%s.json',$this->username);
            if(!file_exists($user)){
                $this->message = sprintf("Username %s not registered.", $this->username);
                return $this->formLogin();        
            }
            $data = json_decode(file_get_contents($user));
            if(password_verify($this->password, $data->password)){
                $_SESSION[$this->config['session_name']] = ($data->profile);
                return $this->success();
            }
            $this->message = sprintf("Hi %s. Your password is mismatch.", $this->username);
        }
        $this->formLogin();
    }

    public function news(){
        if($this->method=="POST"){
            $type = $_POST['type'];
            $title = $_POST['title'];
            $id = $_POST['id'];
            $category = $_POST['category'];
            $tags = $_POST['tags'];
            if($title==''){
                header('HTTP/1.1 304 Not Modified');
                return;
            }
            $new = false;
            if(! $id ){
                $id = date('YmdHis');
                $new = true; 
            }
            $content = $_POST['content'];
            $name = sprintf("%d.json",$id);
            $temp = $this->config['path']['draft'].'/'.$name;
            if(!file_exists($temp)){
                $data = json_decode(file_get_contents($this->config['template']. 'default.json'));
            }else{
                $data = json_decode(file_get_contents($temp));
            }
            if($new){
                $data->meta->id = $id;
                $data->meta->author = $this->session->name;
                $data->meta->date = date('Y-m-d H:i:s').'+07:00';
            }
            $data->meta->title = $title;
            $data->meta->draft = $type=='draft' ? 'true' : ($type=='publish' ?'false' : 'true');
            $data->meta->category = $category;
            $data->meta->tags = $tags;
            $data->content = $content;  
            $json = json_encode($data);

            file_put_contents($temp, $json);
            $this->open('draft');
            $this->open('post');
            return $this->output(json_encode([json_decode($json),$this->draft,$this->post]));
        }

        $path = $this->config['path']['draft'].sprintf('/%d.json',$_GET['id']);    
        if(file_exists($path)){
            $this->output(file_get_contents($path));
        }
    }

    private function loadView(string $view){
        return file_get_contents($this->config['html']. $view .'.html');
    }


    private function formLogin(){
        $this->view(sprintf($this->loadView('login'), $this->username, $this->password, $this->message));
    }

    private function success(){
        $this->view(sprintf($this->loadView('success'), $this->username));
    }

    private function open($name){
        $path = $this->config['path']['draft'];
        $d = dir($path);
        if($d){
            $dir = [];
            $id = [];
            $this->{$name} = '';
            while (false !== ($entry = $d->read())) {
                if(filetype($path.$entry)==='file'){
                    $meta = json_decode(file_get_contents($path.$entry))->meta;
                    if($name=='draft'){
                        if($meta->draft == 'true'){
                            $this->{$name} .= sprintf('<li><a href="%d" class="posts">%s</a></li>',$meta->id, $meta->title);
                        }
                    }else{
                        if($meta->draft == 'false'){
                            $this->{$name} .= sprintf('<li><a href="%d" class="posts">%s</a></li>',$meta->id, $meta->title);
                        }
                    }
                }
            }
        }
        $d->close();        
    }
    
    private function dashboard(){
        $this->open('draft');
        $this->open('post');
        $this->view(sprintf($this->loadView('dashboard'), $this->draft, $this->post));
    }

    private function view($content){
        header("Content-type: text/html");
        echo str_replace("{{ content }}", $content, $this->loadView('template'));
    }

    private function output($data){
        header("Content-type: application/json");
        echo $data;
    }

    private function read($path){
        $file = fopen($path,"r");
        $i = 0;
        $result = '';
        while(! feof($file))
        {
            $line = fgets($file);
            if($i>3){
                $result .= $line;  
            }
            $i++;
        }
        fclose($file);
        echo $result;
    }
}
