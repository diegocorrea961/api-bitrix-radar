<?php

namespace App\Application\Entities;

use App\Application\Repository\BitrixRepository;
use App\Application\Utils\Utils;
use DateTime;
use Exception;
use Slim\Exception\HttpBadRequestException;

date_default_timezone_set('America/Sao_Paulo');

class ContratoGerenciador
{
    use Utils;

    public $bodyContratoGerenciador;

    public function __construct($deal, $company, $fields, $franqueado)
    {
        $this->handlerBodyContratoGerenciador($deal, $company, $fields, $franqueado);
    }

    /**
     * @throws Exception
     */
    private function handlerBodyContratoGerenciador($deal, $company, $fields, $franqueado)
    {
        $lawyerCornerCode = $this->searchLawyerCellCode($deal, $fields, 1);
        if (!$lawyerCornerCode) {
            $this->isRadarCodeFilled($deal['ID'], 'Célula');
        }

        $lawyerCellCode = $this->searchLawyerCode($deal, $fields, 1);
        if (!$lawyerCornerCode) {
            $this->isRadarCodeFilled($deal['ID'], 'Célula');
        }

        $productRadarCode = $this->searchInEnumerations($deal['UF_CRM_1586431691'][0], $fields['UF_CRM_1586431691']);
        if (!$productRadarCode) {
            $this->isRadarCodeFilled($deal['ID'], 'Produto');
        }

        $listRateios = $this->listRateios(
            $deal['OPPORTUNITY'],
            $lawyerCornerCode,
            $lawyerCellCode,
            $productRadarCode
        );

        $finalDate = $this->dateNowAndAddDays($deal['DATE_CREATE'], 12);
        $data = new DateTime($deal['UF_CRM_1554319921']);

        $this->bodyContratoGerenciador = [
            'CPF_CNPJ_Cliente' => $deal['UF_CRM_1646233130394'],
            'CodigoDocumento' => '004',
            'NumeroContrato' => $deal['UF_CRM_1561394928'],
            'CodigoFilial' => $lawyerCornerCode,
            'DataContratoInicial' => date("d/m/Y", strtotime($deal['UF_CRM_1656425295'])),
            'DataContratoFinal' => $finalDate['finalDate'],
            'DataFaturamento' => date("d/m/Y", strtotime($deal['UF_CRM_1656425295'])),
            'DataVencimento' => $data->format('d/m/Y'),
            'DescricaoContrato' => $this->searchProduct($deal, $fields, 0),
            'QuantidadeParcelas' => 0,
            'Rateios' => [$listRateios],
            'Nome' => $company['TITLE'],
            'RazaoSocialCliente' => $company['TITLE'],
            'RenovacaoAutomatica' => true,
            'Situacao' => 1,
            'Classificacao' => $productRadarCode,
            'TipoFaturamento' => 10, // $this->decideBillingType($deal["UF_CRM_1553026310"]),
            'UtilizaParcelamento' => false,
            'ValorOriginal' => str_replace(".", ",", $deal["OPPORTUNITY"]),
            'DadosInfoPlus' => [
                [
                    "Descricao" => "Tipo Contrato",
                    "Grupo" => "1",
                    "IdHeader" => 350,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 7,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Valor no Exito (%):",
                    "Grupo" => "",
                    "IdHeader" => 200,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 4,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Franqueado",
                    "Grupo" => "2",
                    "IdHeader" => 351,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 7,
                    "Valor" => $franqueado['UF_USR_1689948220200']
                ],
                [
                    "Descricao" => "Mês Venda",
                    "Grupo" => "",
                    "IdHeader" => 50,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 1,
                    "Valor" => date("m/Y", strtotime($deal['BEGINDATE']))
                ],
                [
                    "Descricao" => "Valor da Parcela",
                    "Grupo" => "",
                    "IdHeader" => 201,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 4,
                    "Valor" => (string)round(floatval(explode('| ', $deal['UF_CRM_1554319938'])[0]), 2)
                ],
                [
                    "Descricao" => "Qtde Parcelas",
                    "Grupo" => "",
                    "IdHeader" => 150,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 3,
                    "Valor" => (string)$deal['UF_CRM_1553026310']
                ],
                [
                    "Descricao" => "Motivo do Cancelamento",
                    "Grupo" => "3",
                    "IdHeader" => 353,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 7,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Observação Cancelamento",
                    "Grupo" => "",
                    "IdHeader" => 100,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 2,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Mês Protocolo(US)",
                    "Grupo" => "",
                    "IdHeader" => 51,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 1,
                    "Valor" => date("m/Y", strtotime($deal['BEGINDATE']))
                ],
                [
                    "Descricao" => "Unidade",
                    "Grupo" => "1",
                    "IdHeader" => 352,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 7,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Êxito Limpa Nome",
                    "Grupo" => "",
                    "IdHeader" => 202,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 4,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Rate",
                    "Grupo" => "",
                    "IdHeader" => 203,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 4,
                    "Valor" => ""
                ]
            ]
        ];
    }

    public function getBodyContratoGerenciador()
    {
        return $this->bodyContratoGerenciador;
    }

    /**
     * @throws Exception
     */
    private function isRadarCodeFilled($dealId, $type)
    {
        $bitrixRepository = new BitrixRepository();
        $message = "Não foi encontrado um valor de {$type} no deal ou o código do Radar não foi preechido no campo";
        $bitrixRepository->messageBitrix(
            $dealId,
            json_encode(['Funcao' => 'GravarContratoGerenciador', 'Mensagem' => $message]),
            true
        );
        throw new Exception($message, 400);
    }
}
