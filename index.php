<?php
require __DIR__ . "/vendor/autoload.php";

use Src\models\Candidato;
use Src\App\Email;

//style.css
echo "<link rel='stylesheet' href='./src/assets/style.css'/>";

date_default_timezone_set(TIME_ZONE_AMERICA_SAO_PAULO);

$email = new Email();

//messages feedback
$info =message();

//Instância do modelo
$candidato = new Candidato();

//obtem os dados do form
$data = filter_input_array(INPUT_POST,FILTER_DEFAULT);

$candidato->nome =$data['nome'];
$candidato->telefone =$data['telefone'];
$candidato->email =$data['email'];
$candidato->cargo_desejado =$data['cargo_desejado'];
$candidato->escolaridade =$data['escolaridade'];
$candidato->observacoes =$data['observacoes'] ?: 'null';
$candidato->ip_address =get_ip_address();
$candidato->created_at =date_mysql();    

$emailData =date_br($candidato->created_at);

$email->add(
    "Processo Seletivo de Desenvolvedor Full Stack no Paytour.",
    "<h2 style='color:navy'>Inscrição</h2>
        <p>Nome: {$candidato->nome}</p>
        <p>Tel: {$candidato->telefone}</p>
        <p>Email: {$candidato->email}</p>
        <p>Cargo: {$candidato->cargo_desejado}</p>
        <p>Escolaridade: {$candidato->escolaridade}</p>
        <p>Registrado: {$emailData}</p>",
    "Raphael Cunha",
    "utilserra.com@gmail.com"//substituir pelo email dev@paytour.com.br 
);

$file =[];
if($_FILES && !empty($_FILES['file']['name'])) {
    $file =$_FILES['file'];
}   

$types =["application/msword","application/pdf",'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

//um email somente para uma vaga
$exist =(bool)$candidato->find("email =:e AND cargo_desejado =:cd","e={$data['email']}&cd={$data['cargo_desejado']}")->fetch();


//filter tags
if($data) {
    $strip = array_map("strip_tags",$data);
    $filter =array_map("trim",$strip);
    //validação
    if(empty($filter['nome'])) {
        $info = message("warning","Insira o nome completo!",true);
    }elseif(!filter_var($data['email'],FILTER_VALIDATE_EMAIL)) {
        $info = message("warning","Insira um E-mail Válido!",true);
    }elseif($exist) {
        $info = message("warning","Você já se candidatou à esta vaga, tente outras vagas!",true);
    }elseif(empty($filter['telefone'])) {
        $info = message("warning","Insira o Telefone!",true);
    }elseif(empty($filter['cargo_desejado'])) {
        $info = message("warning","Insira o Cargo desejado!",true);
    }elseif(empty($filter['escolaridade'])) {
        $info = message("warning","Insira o Grau de escolaridade!",true);
    }else{
        
        if($file) {
            $size =(int)$file['size'];
            $allowedSize =$size < 1048576 ? true : false;
            $filename =new_file_name($filter['nome'],$file['name']);
            $folder = __DIR__ ."/src/uploads/curriculos/{$filename}";
            if(in_array($file['type'],$types)) {
                if($allowedSize) {
                    if(move_uploaded_file($file['tmp_name'],$folder)) {
                        $candidato->arquivo = UPLOAD_CURRICULO.$filename;
                        if($candidato->save()) {
                            if($email->send()) {
                                $info = message("success","Parabéns! sua matrícula foi efetuada com sucesso.",true);
                            }else{
                                $info =message("danger",$email->error()->getMessage(),true);
                            }
                        }else{
                            $info = message("danger",$candidato->fail()->getMessage(),true);
                        }
                    }else{
                        $info = message("danger","Algo deu errado, tente novamente mais tarde!",true);
                    }
                }else{
                    $info = message("danger","O Arquivo é grande demais para o upload!",true);
                }
            }else{
                $info = message("warning","Arquivo inválido, Somente arquivos (.pdf - .doc - .docx)",true);
            }
        }else{
            $info = message("warning","Selecione seu currículo para enviar!",true);
        }
    }
}

require __DIR__.'/src/views/form.php';