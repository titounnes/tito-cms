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
        $this->post = [];
        $this->categories = '';
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

    public function logout(){
        session_destroy();
        header("location: index.php");
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
            
            $meta = [
                'id' => $id,
                'category' => $category,
                'title' => $title,
                'author' =>  $this->session->name,
                'draft' => $type=='draft' ? 'true' : ($type=='publish' ?'false' : 'true'),
                'date' => date('Y-m-d H:i:s',strtotime($id)),
                'tags' => $tags
            ];
            if($category != $_SESSION['category'] && $id==$_SESSION['id_article']){
                unlink($this->config['path']['hugo'].$_SESSION['category'].'/'.$id.'.md');
            }
            
            if(!$category){
                $category = 'Uncategories';
            }
            $_SESSION['category'] = $category;
            $_SESSION['id'] = $id;

             
            $path = $this->config['path']['hugo'].$category.'/';
            if(!is_dir($path)){
                mkdir($path, 0755);
            }
            $text = "---\n";
            foreach($meta as $key=>$val){
                $text .= sprintf("%s: %s\n",$key,$val);
            }
            $text .= "---\n\n";
            $text .= $content;

            $path = $this->config['path']['hugo'].sprintf('%s/%s.md',$category, $id);
            file_put_contents($path, $text);
            $this->open();
            return $this->output(json_encode(['id'=>$id,'draft'=>$type]));
        }

        $path =$this->config['path']['hugo'];
        $id = $_GET['id'];
        $dir = dir($path);
        $category = '';
        $target = '';
        $target = $path;
        while (false !== ($entry = $dir->read())) {
            if(filetype($path.$entry)==='dir' &&  ! in_array($entry, ['.','..'] )){
                $category = $entry;
                $subpath = $path.$entry.'/';
                $subdir = dir($subpath);
                while(false !== ($subentry = $subdir->read())){
                    if(filetype($subpath.$subentry)==='file' && $subentry == $id.'.md'){
                        $target = $subpath.$subentry;
                        break;
                    }
                }
            }
        }
        $dir->close();
        $_SESSION['id_article'] = $id;
        $_SESSION['category'] = $category;
        $this->output(json_encode($this->getContent($target)));
    }

    private function generate($data, $type){
        $meta = $data->meta;
        if(!$meta->category){
            $meta->category = 'uncategorized';
        }
        $category = $this->config['path']['hugo'].$meta->category.'/';
        if(!is_dir($category)){
            mkdir($category, 0755);
        } 
        $text = "---\n";
        foreach($meta as $key=>$val){
            if(in_array($key, ['title','author','description','featured_image','featured_video','category'])){
                $text .= sprintf("%s: \"%s\"\n",$key,$val);
            }else if($key=='tags'){
                $text .= sprintf("%s: [\"%s\"]\n",$key,implode("\",\"",explode(";",str_replace([",","; ",", "],";",$val))));
            }else{
                $text .= sprintf("%s: %s\n",$key,$val);
            }
            
        }
        $text .= "---\n\n";
        $text .= $data->content;
        $path = $category.$data->meta->id.'.md';
        file_put_contents($path, $text);
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

    private function open(){
        $path =$this->config['path']['hugo'];
        $dir = dir($path);
        $this->categories = '<option value="">Choose One</option>';
        $categories = [];
        $this->post = '';
        while (false !== ($entry = $dir->read())) {
            if(filetype($path.$entry)==='dir' &&  ! in_array($entry, ['.','..'] )){
                if(!in_array($entry, $categories)){
                    $categories[] = $entry;
                    $this->categories .= sprintf('<option value="%s">%s</option>',$entry,$entry); 
                }
                $subpath = $path.$entry.'/';
                $subdir = dir($subpath);
                while(false !== ($subentry = $subdir->read())){
                    if(filetype($subpath.$subentry)==='file'){
                        $ext = explode('.', $subentry);
                        if($ext[1] =='md'){
                            $meta = $this->getMeta($subpath.$subentry);
                            $this->post .= sprintf("<li><a href=\"%d\" class=\"posts\">%s <span id=\"status-%s\" class=\"badge\">%s</span></a></li>",$ext[0], trim($meta['title'],'"'), $ext[0], $meta['draft']=='true' ? 'Draft' : 'Publish' ); 
                        }
                    }
                }
            }
        }
        $dir->close();
    }
    
    private function getMeta($path){
        $content = file_get_contents($path);
        $a = strpos($content, '---',  3);
        $metaMd = substr($content, 0, $a);
        $metaObj = explode("\n", $metaMd);
        $len = count($metaObj);
        $meta = [];
        foreach($metaObj as $k=>$val){
            if($k>0 && $k <$len-1){
                $metadata = explode(': ', $val);
                $meta[$metadata[0]] = $metadata[1];
            }
        }
        return $meta; 
    }

    private function getContent($path){
        if(!file_exists($path)){
            return false;
        }
        $content = file_get_contents($path);
        $a = strpos($content, '---',  3);
        $metaMd = substr($content, 0, $a);
        $metaObj = explode("\n", $metaMd);
        $len = count($metaObj);
        $meta = [];
        foreach($metaObj as $k=>$val){
            if($k>0 && $k <$len-1){
                $metadata = explode(': ', $val);
                if(in_array($metadata[0], ['title','description'])){
                    $meta[$metadata[0]] = trim($metadata[1],'"');
                }else if($metadata[0]=='tags'){
                    $meta[$metadata[0]] = str_replace('","',"; ",trim($metadata[1], '"[]'));
                }else{
                    $meta[$metadata[0]] = $metadata[1];
                }
                
            }
        }

        return ['meta'=>$meta, 'content' => $content = str_replace($metaMd."---\n\n", '', $content)]; 
    }

    private function dashboard(){
        $this->open();
        $this->view(sprintf($this->loadView('dashboard'), $this->post, $this->categories), $this->loadView('menu_'.$this->session->role));
    }

    private function view($content, $menu= ''){
        header("Content-type: text/html");
        $html = str_replace("{{ content }}", $content, $this->loadView('template'));
        $html = str_replace("{{ menu }}", $menu, $html);
        echo $html;
    }

    private function output($data){
        header("Content-type: application/json");
        echo $data;
    }
}
