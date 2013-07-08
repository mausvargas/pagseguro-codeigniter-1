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
	
	/**
	 * Pagseguro
	 *
	 * @access public
	 * @param int(11) idVenda
	 */
	public function pagseguro($idVenda) {
		if($this->session->userdata('logged_in') == true && $this->session->userdata('userData')->idTipoUsuario == 4) {
	
			$this->data['hasError'] = false;
			$this->data['errorList'] = array();
	
			$venda = array_shift($this->Vendas_model->getVenda(array('idVenda' => $idVenda)));
			// validações
	
			if(!is_object($venda)) {
				$this->data['hasError'] = true;
				$this->data['errorList'][] = array('message' => 'Não foi possível localizar sua compra.');
			}
	
			if(!$this->data['hasError']) {
				$userObj = $this->session->userdata ( 'userData' );
				$promocao = $this->Promocoes_model->getPromocaoById($venda->idPromocao);
	
				// Pega o estado do usuário
				$estadoObj = null;
				if($userObj->idEstado) {
					$filter = array ('idEstado' =>  $userObj->idEstado);
					$estadoObj = array_shift ( $this->Estados_model->getEstado ( $filter ) );
				}
	
				// Instantiate a new payment request
				$paymentRequest = new PagSeguroPaymentRequest ();
	
				// Sets the currency
				$paymentRequest->setCurrency ( "BRL" );
	
				// Sets a reference code for this payment request, it is useful to
				// identify this payment in future notifications.
				$paymentRequest->setReference ( $venda->idVenda );
	
				// Add an item for this payment request
				$paymentRequest->addItem ( '0001', truncate(utf8_decode($promocao->nome), 80, '...'), 1, number_format ( $venda->valorDevido, 2, '.', '' ) );
	
				$paymentRequest->setShippingType ( 3 );
				$paymentRequest->setShippingAddress ( str_replace ( '-', '', str_replace ( '.', '', $userObj->CEP ) )
						, utf8_decode($userObj->endereco)
						, $userObj->numero
						, utf8_decode($userObj->complemento)
						, utf8_decode($userObj->bairro)
						, utf8_decode($userObj->cidade)
						, (($estadoObj->sigla)? $estadoObj->sigla : ''), 'BRA' );
	
				// Sets your customer information.
				$telefone = numbersOnly($userObj->telefone1);
				$paymentRequest->setSenderName(utf8_decode(truncate($userObj->nome, 49)));
				$paymentRequest->setSenderEmail($userObj->email);
				$paymentRequest->setSenderPhone(substr ( $telefone, 0, 2 ), substr ( $telefone, 2, 8 ));
	
				$paymentRequest->setRedirectUrl ( base_url('ofertas/retornoPagamento') );
				$paymentRequest->setMaxAge(86400 * 3);
	
				try {
					$credentials = new PagSeguroAccountCredentials ( $this->config->item ( 'pagseguroAccount' ), $this->config->item ( 'pagseguroToken' ) );
					$url = $paymentRequest->register ( $credentials );
	
					$dados = array(
							'meioPagamento' => 2
							,'statusPagamento' => 1
							,'dataAtualizacao' => date('Y-m-d H:i:s')
					);
	
					$this->Vendas_model->update($dados, $venda->idVenda);
					redirect ( $url );
	
				} catch ( PagSeguroServiceException $e ) {
					$this->data['hasError'] = true;
					$this->data['errorList'][] = array('message' => 'Ocorreu um erro ao comunicar com o Pagseguro.' .$e->getCode() . ' - ' .  $e->getMessage());
				}
	
				var_dump($this->data['errorList']);
			}
		} else {
			redirect(base_url('login'));
		}
	}
	
	/**
	 * retornoPagamentoPagseguro
	 *
	 * Recebe o retorno de pagamento da promoção via pagseguro
	 * @access public
	 * @return void
	 */
	public function retornoPagamento() {
		$transaction = false;
	
		// Verifica se existe a transação
		if ($this->input->get ( 'idTransacao' )) {
			$transaction = self::TransactionNotification ( $this->input->get ( 'idTransacao' ) );
		}
	
		// Se a transação for um objeto
		if (is_object ( $transaction )) {
			self::setTransacaoPagseguro($transaction);
		}
	
		redirect ( base_url('minha-conta') );
	}
	
	/**
	 * setTransacaoPagseguro
	 *
	 * Seta os status da transação vindas do Pagseguro
	 *
	 * @param array $transaction
	 * @return void
	 */
	private function setTransacaoPagseguro($transaction = null) {
		// Pegamos o objeto da transação
		$transactionObj = self::getTransaction ( $transaction );
	
		// Buscamos a venda
		$filter = array ('idVenda' => $transactionObj ['reference']);
		$vendaList = $this->Vendas_model->getVenda ( $filter );
	
		// existindo a venda
		if (is_array ( $vendaList ) && sizeof ( $vendaList ) > 0) {
			$venda = array_shift($vendaList);
	
			// Aguardando pagamento
			if ($transactionObj ['status'] == 1) {
	
				$dados = array(
						'meioPagamento' => 2
						,'statusPagamento' => 1
						,'idTransacao' => $transaction->getCode()
						,'dataAtualizacao' => date('Y-m-d H:i:s')
				);
	
				$this->Vendas_model->update($dados, $venda->idVenda);
			}
	
	
			// Aguardando aprovação
			if ($transactionObj ['status'] == 2) {
				$dados = array(
						'meioPagamento' => 2
						,'statusPagamento' => 2
						,'idTransacao' => $transaction->getCode()
						,'dataAtualizacao' => date('Y-m-d H:i:s')
				);
	
				$this->Vendas_model->update($dados, $venda->idVenda);
			}
	
			// Transação paga
			if ($transactionObj ['status'] == 3) {
	
				$lastEvent = strtotime($transaction->getLastEventDate());
	
				$dados = array(
						'statusPagamento' => 3
						,'valorPago' =>  $transaction->getGrossAmount()
						,'taxas' => $transaction->getFeeAmount()
						,'idTransacao' => $transaction->getCode()
						,'dataAtualizacao' => date('Y-m-d H:i:s')
						,'dataCredito' => date('Y-m-d H:i:s', $lastEvent)
				);
	
				$this->Vendas_model->update($dados, $venda->idVenda);
			}
	
			// Pagamento cancelado
			if ($transactionObj ['status'] == 7 && $venda->statusPagamento != 3) {
				$dados = array(
						'meioPagamento' => 2
						,'statusPagamento' => 7
						,'taxas' => $transaction->getFeeAmount()
						,'idTransacao' => $transaction->getCode()
						,'dataAtualizacao' => date('Y-m-d H:i:s')
				);
	
				$this->Vendas_model->update($dados, $venda->idVenda);
			}
		}
	}
	
	/**
	 * getTransaction
	 *
	 * Método para buscar a transação no pag reguto
	 * @access public
	 * @param PagSeguroTransaction $transaction
	 * @return array
	 */
	public static function getTransaction(PagSeguroTransaction $transaction) {
		return array ('reference' => $transaction->getReference (), 'status' => $transaction->getStatus ()->getValue () );
	}
	
	/**
	 * NotificationListener
	 *
	 * Recebe as notificações do pagseguro sobre atualização de pagamento.
	 * @access public
	 * @return bool
	 */
	public function NotificationListener() {
	
		$code = (isset ( $_POST ['notificationCode'] ) && trim ( $_POST ['notificationCode'] ) !== "" ? trim ( $_POST ['notificationCode'] ) : null);
		$type = (isset ( $_POST ['notificationType'] ) && trim ( $_POST ['notificationType'] ) !== "" ? trim ( $_POST ['notificationType'] ) : null);
		$transaction = false;
	
		if ($code && $type) {
	
			$notificationType = new PagSeguroNotificationType ( $type );
			$strType = $notificationType->getTypeFromValue ();
	
			switch ($strType) {
	
				case 'TRANSACTION' :
					$transaction = self::TransactionNotification ( $code );
					break;
	
				default :
					LogPagSeguro::error ( "Unknown notification type [" . $notificationType->getValue () . "]" );
	
			}
		} else {
	
			LogPagSeguro::error ( "Invalid notification parameters." );
			self::printLog ();
		}
	
		if (is_object ( $transaction )) {
			self::setTransacaoPagseguro($transaction);
		}
	
		return TRUE;
	}
	
	/**
	 * TransactionNotification
	 *
	 * Recupera a transação através de uma notificação
	 * @access private
	 * @param unknown_type $notificationCode
	 * @return Ambigous <a, NULL, PagSeguroTransaction>
	 */
	
	private static function TransactionNotification($notificationCode) {
		$CI = & get_instance ();
		$credentials = new PagSeguroAccountCredentials ( $CI->config->item ( 'pagseguroAccount' ), $CI->config->item ( 'pagseguroToken' ) );
	
		try {
			$transaction = PagSeguroNotificationService::checkTransaction ( $credentials, $notificationCode );
		} catch ( PagSeguroServiceException $e ) {
			die ( $e->getMessage () );
		}
	
		return $transaction;
	}
	
	/**
	 * Método que registra logs do pagseguro
	 * @access private
	 * @param String $strType
	 */
	private static function printLog($strType = null) {
		$count = 30;
		echo "<h2>Receive notifications</h2>";
		if ($strType) {
			echo "<h4>notifcationType: $strType</h4>";
		}
		echo "<p>Last <strong>$count</strong> items in <strong>log file:</strong></p><hr>";
		echo LogPagSeguro::getHtml ( $count );
	}
}
/* End of file exemplo.php */
/* Location: ./application/controllers/exemplo.php */