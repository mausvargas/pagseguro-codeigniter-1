<?php if (! defined ( 'BASEPATH' )) exit ( 'No direct script access allowed' );

class Ofertas extends CI_Controller {
	private $pgAccount;
  private $pgToken;
	
	/**
	 * Construtor da classe
	 */
	public function __construct() {
		parent::__construct ();
		$this->load->config('pagseguro');
		$this->load->library('PagSeguroLibrary');
		
		$this->pgAccount = $this->config->item ( 'pagseguroAccount' );
		$this->pgToken = $this->config->item ( 'pagseguroToken' );
	}
}
/* End of file exemplo.php */
/* Location: ./application/controllers/exemplo.php */