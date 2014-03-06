<?php
// Restringe el Acceso al archivo si no es desde el Entorno Joomla.
defined('_JEXEC') or die;

/**
 * --- LJAH Falta documentar todo esto correctamente
 * Plugin CieloThorhospedaje
 */

class plgThorhospedajeCieloThorhospedaje extends JPlugin
{
	private $_titulo = '<h3 style="color: %s">%s</h3>';
	private $cards = array('visa_electron','redeshop_maestro','visa','mastercard','dinersclub','americanexpress','elo','discover','jcb','aura');

/**
 * Constructor del objeto
 * 
 * @param object &$subject es una instancia del objeto a construir
 * @param array $config parámetro opcional arreglo asociativo: puede contener las claves
 *                      'name', 'group', 'params', 'language'
 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}//fin constructor

/**
 * --- LJAH Falta documentar todo esto correctamente
 * Esta función es ejecutada antes de renderizar el articulo
 *
 * @param String $context es el contexto en el cual es ejecutada
 * @param Object $row es un objeto que contiene la información del articulo(como titulo, contenido, url, autor, etc)
 * @param Object $params es un objeto que contiene la información del portal y parametros del plugin
 * @param Integer $limitstart desplazamiento del elemento
 */
	public function onTHShowPay($context, $params)
	{	
		$monedas = array(0 => '$', 1 => '', 2 => '$', 3 => 'Bs');
		
		$cardsImages = array('visa_electron.png','redeshop.png','visa.png','mastercard.png','diners.png','amex.png','elo.png','discover.png','jcb.png','aura.png');
		$html = "<ul class='nav nav-tabs' id='pay-method'>";
		$i = 0;
		foreach ($this->cards as $card)
		{
			$i++;
			if ($this->params->get($card . '_enabled'))
			{
				$image = sprintf("<img src='". JURI::root() . "plugins/thorhospedaje/cielothorhospedaje/images/%s' />",$cardsImages[$i-1]);
				$html .= sprintf("<li class=''><a data-toggle='tab' href='#tab%s'>%s</a> </li>",
					$i, $image);
			}
			
		}
		
		$html .= "</ul>";
		$html .= JHtml::_('bootstrap.startPane', 'pay-method', NULL	);
		$i = 0;
		foreach ($this->cards as $card)
		{
			$i++;
			if ($this->params->get($card . '_enabled'))
			{
				$tabname = sprintf("tab%s",$i);
				$html .= JHtml::_('bootstrap.addPanel', 'pay-method', $tabname);
				$html .= sprintf("<h3>%s</h3>",$this->params->get($card . '_name'));
				if ($this->params->get($card . '_max_without_interest', NULL) == NULL)
				// Es tarjeta de débito
				{
					// Aca debe llenarse el monto final de la compra, temporalmente se hará así
					$monto_compra = $params['monto'];
					$html .= sprintf('<input type="radio" name="parcelas" value="%s_A" /> 1 parcela de %s', $card, $monedas[$params['pais']]);
					// Descuento
					if (($this->params->get($card . '_discount', NULL) != NULL) && ($this->params->get($card . '_discount', NULL) != 0))
					{
						$monto_compra = $monto_compra - ($monto_compra * ($this->params->get($card . '_discount', NULL) / 100));
					}
					$html .= sprintf('%d (con %s%% de descuento)', $monto_compra, $this->params->get($card . '_discount', NULL));
					// Descuento
				}else
				// Es tarjeta de crédito
				{
					// Aca debe llenarse el monto final de la compra, temporalmente se hará así
					$monto_compra = $params['monto'];
					$html .= sprintf('<input type="radio" name="parcelas" value="%s_1" /> 1 parcela de %s', $card, $monedas[$params['pais']]);
					// Descuento
					if (($this->params->get($card . '_discount', NULL) != NULL) && ($this->params->get($card . '_discount', NULL) != 0))
					{
						$monto_compra = $monto_compra - ($monto_compra * ($this->params->get($card . '_discount', NULL) / 100));
					}
					$html .= sprintf('%d (con %s%% de descuento) <br />', $monto_compra, $this->params->get($card . '_discount', NULL));
					// Descuento
					for ($k = 0; $k < $this->params->get($card . '_max_without_interest', NULL); $k++)
					{
						$html .= sprintf('<input type="radio" name="parcelas" value="%s_%d" /> %s parcelas de %s%.2f (sin intereses) <br />', $card, $k+2, $k+2, $monedas[$params['pais']], ($params['monto']/($k+2)));
					}
					
					// Falta el pago con intereses
				}
				$html .= JHtml::_('bootstrap.endPanel');
			}
			
		}
		$html .= JHtml::_('bootstrap.endPane', 'pay-method');

		
		
		
		return $html;
	}//fin onContentPrepare
	

/**
 * --- LJAH Falta documentar todo esto correctamente
 * Esta función es ejecutada antes de renderizar el articulo
 *
 * @param String $context es el contexto en el cual es ejecutada
 * @param Object $row es un objeto que contiene la información del articulo(como titulo, contenido, url, autor, etc)
 * @param Object $params es un objeto que contiene la información del portal y parametros del plugin
 * @param Integer $limitstart desplazamiento del elemento
 */
	public function onTHExecutePay($context, $params)
	{	
		jimport('cielo_thorhospedaje.cielo.cielo');
		$app =& JFactory::getApplication();

		$Pago = new THCielo();
		$card = $Pago->formaPagamentoBandeira = $params["formaPagamentoBandeira"]; 
		if($params["formaPagamento"] != "A" && $params["formaPagamento"] != "1")
		{
			$Pago->formaPagamentoProduto = $params["tipoParcelamento"];
			$Pago->formaPagamentoParcelas = $params["formaPagamento"];
		} 
		else 
		{
			$Pago->formaPagamentoProduto = $params["formaPagamento"];
			$Pago->formaPagamentoParcelas = 1;
		}
		
		
		
		$Pago->dadosEcNumero = $this->params->get('n_establecimiento');
		$Pago->dadosEcChave = $this->params->get('cielo_key');
		
		$Pago->capturar = $this->params->get($card . '_cap_auto');	
		$Pago->autorizar = $this->params->get($card . '_auth_type');
		
		$Pago->dadosPedidoNumero = rand(1000000, 9999999); // LJAH: Debe generarse un número válido
		$Pago->dadosPedidoValor = 100; // LJAH: Falta enviar el precio del producto correctamente, esto es 1,00 Reales BR
		$Pago->urlRetorno = $params["urlRetorno"];
		
		// ENVIA REQUISIÇÃO SITE CIELO
		//echo "ENVIA REQUISIÇÃO SITE CIELO"; 
		$objResposta = $Pago->RequisicaoTransacao(false);
		$Pago->tid = $objResposta->tid;
		$Pago->pan = $objResposta->pan;
		$Pago->status = $objResposta->status;
		$urlAutenticacao = "url-autenticacao";
		$Pago->urlAutenticacao = $objResposta->$urlAutenticacao;
		
		$StrPago = $Pago->ToString();
		$app->setUserState('thorhospedaje.posaderos.pago', $StrPago);
		
		$html = '<script type="text/javascript">
				window.location.href = "' . $Pago->urlAutenticacao . '"
			 </script>';

		return $html;
	}//fin onTHExecutePay
}
