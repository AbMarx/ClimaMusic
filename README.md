# API REST Clima Music

<h3>Introdução a API Clima Music</h3>

O objetivo desta documentação é orientar o desenvolvedor sobre como integrar com o API do Clima Music, API que irá disponibilizar músicas de acordo com a temperatura de uma cidade e gerador de métricas das requisições, descrevendo os serviços disponíveis com exemplos de requisição e respostas.

Todas as operações requerem credenciais de acesso (Client ID e Client Secret) específicos para respectivos para comunicação com a API Spotify e key para a API externa OpenWeatherMaps, mas não se preocupe, essas credenciais são mantidas e manipuladas do nosso lado.

<h3>Arquitetura</h3>

A integração é realizada através de serviços disponibilizados como Web Services. O modelo utilizado é bem simples e fácil de ser utilizado, leia abaixo as especificações dos endpoints disponíveis nesta API que são comunicáveis através do protocolo HTTP.

<b>GET</b> - O método HTTP GET é utilizado para consultas de recursos já existentes. Por exemplo, músicas com base na temperatura da cidade e estatísticas das requsições já realizadas.
Instalação

O nosso serviço é disponibilizado via API REST a qual não necessita de nenhuma instalação, basta você consumir os dados disponibilizados em nossos endpoints desta documentação.
