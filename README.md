Plugin feito para rotear as requisições que chegam de um webhook para um outro ambiente via Ngrok sem precisar alterar o remetente da requisição.

Ex: Preciso realizar testes em meu ambiente local, os webhooks das transações estão configurados no servidor de homologação, e não tenho acesso as para alterar as configurações na origem. Assim utilizo este plugin para capturar as requisições no ambiente de homologação e enviar para o meu ambiente local.

Exemplo de utilização do ngrok 

```shell
 ngrok http 80 --host-header=getnet.loc
```

Atualizar a url retornada do Ngrok em Configurações -> Webhook Proxy