Pagseguro Code Igniter
=====================

Adaptação da API PagSeguro Library Class 2.0.3 para utilização com Code Igniter.

Instalação
=====================

Baixe os arquivos de integração do repositório e extraia dentro da sua aplicação.
Sete permissão 777 para ``./application/libraries/``

Como usar
=====================

Configure os parâmetros da configuração do Pag Seguro em ``./application/config/pagseguro.php``

    <?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    
    $config['pagseguroAccount'] = 'email@dominio.com.br'; // email de acesso ao painel do pagseguro.
    $config['pagseguroToken'] = 'xxxxxxxxxx'; // token do pagseguro
    ?>

Na seu controller:


    <?php
    
    class MeuController extends CI_Controller {
      private $pgAccount;
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

Pronto! Agora basta vc usar os métodos para pagamento do Pag Seguro.
OBS: Veja o arquivo de exemplo no repositório.

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
