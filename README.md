Pagseguro Code Igniter
=====================

Adaptação da API Pagseguro para utilização com Code Igniter.

Como usar
=====================

Configure os parâmetros da configuração do Pag Seguro em ./application/config/pagseguro.php

<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['pagseguroAccount'] = 'email@dominio.com.br'; // email de acesso ao painel do pagseguro.
$config['pagseguroToken'] = 'xxxxxxxxxx'; // token do pagseugor
?>

Na seu controller:

<?php

class MeuController extends CI_Controller {
  provate $pgAccount;
  private $pgToken;
  
  public function __construct() {
  	parent::__construct ();
    
    $this->load->config('pagseguro');
    $this->load->library('PagSeguroLibrary');
    
    $this->pgAccount = $this->config->item ( 'pagseguroAccount' );
    $this->pgToken = $this->config->item ( 'pagseguroToken' );
	}
}
?>

Pronto! Agora basta vc usar os métodos para pagamento do Pag Seguro

Autoload Code Igniter
=====================

Caso você queira colocar os arquivos no autoload, basta adicionar os parâmetros no array, conforme abaixo:

<?php
...
$autoload['libraries'] = array('PagSeguroLibrary');
...
$autoload['config'] = array('pagseguro');
...
?>
