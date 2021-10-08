<div align="center">

# Catálogo Digital para WhatsApp

![Issues](https://img.shields.io/github/issues/MatheusGatti/catalogo-digital-whatsapp) ![Forks](https://img.shields.io/github/forks/MatheusGatti/catalogo-digital-whatsapp) ![Stars](https://img.shields.io/github/stars/MatheusGatti/catalogo-digital-whatsapp) ![License](https://img.shields.io/github/license/MatheusGatti/catalogo-digital-whatsapp)
  
</div>


Esse projeto foi desenvolvido especificamente para a minha loja mas decidi colocá-lo no GitHub pois procurei projetos parecidos e não encontrei.

É um projeto bem simples onde tive como principal objetivo ter um catálogo digital para conseguir agilizar o atendimento ao cliente que consegue visualizar todos os produtos que estão disponíveis, colocá-los no carrinho e finalizar seu pedido, assim que finalizado recebemos uma notificação de pedido finalizado no Telegram onde podemos ir direto para o pedido no site do catálogo e enviar uma mensagem com o pedido detalhado para o cliente no WhatsApp.

## Funcionalidades

 - [x] Painel de controle
	 - [x] Identifica se o usuário veio por outro caminho mas não estava logado para redirecionar para o mesmo caminho após logar.
	 - [x] Controle de usuários
		 - [x] Administrador: consegue editar usuários e todas outras funções
		 - [ ] Colaborador: consegue apenas ver e editar os pedidos
	- [x] Controle de dados
		- [x] Cadastrar/(des)ativar/excluir formas de pagamento
		- [x] Cadastrar/(des)ativar/excluir formas de entrega
		- [x] Cadastrar/(des)ativar/excluir descontos
	- [x] Controle de categorias de produtos
		- [x] Cadastrar/editar/(des)ativar/excluir categorias
	- [x] Controle de produtos
		- [x] Cadastrar/editar/(des)ativar/excluir produtos
		- [x] Controle de estoque (quantidade de produtos disponíveis)
		- [x] Filtro de produtos por nome, estoque, status (ativado/desativado) e categoria.
	- [x] Controle de pedidos
		- [x] 3 status de pedido:
			- [x] Novo (não foi atendido no WhatsApp)
				- [x] Botão para enviar mensagem para o WhatsApp do cliente já com o pedido detalhado com o número do pedido, nome completo do cliente, forma de pagamento, forma de entrega, produtos e valor total já com desconto caso tenha
			- [x] Atendido (foi atendido no WhatsApp mas não foi finalizado)
				- [x] O mesmo botão do status de novo.
			- [x] Finalizado (pedido finalizado/vendido)
				- [x] Botão para agradecer o cliente no WhatsApp.
		- [x] Excluir pedido
			- [x] Voltar produtos para o estoque
			- [x] Não voltar produtos para o estoque
		- [x] Filtrar pedidos por número do pedido, nome, sobrenome, status, atendente, data do pedido
		- [x] Ao pedido ser atendido ou finalizado é marcado automaticamente por quem foi feita a ação
- [x] Página inicial
	- [x] Mostra as categorias dos produtos
	- [x] Mostra todos os produtos disponíveis com paginação e filtro de busca por nome do produto
- [x] Página de categoria
	- [x] Mostra todos os produtos disponíveis da categoria com paginação e filtro de busca por nome do produto
	- [x] Os produtos são ordenados dos mais caros para os mais baratos como uma estratégia de preços
- [x] Página do produto
	- [x] Mostra o produto detalhadamente com todas as fotos e descrição caso tenha
	- [x] Opção de adicionar/remover mais produtos do mesmo
		- [x] Não deixa de forma alguma o valor ser menor ou igual a 0
		- [x] Não deixa de forma alguma o valor ultrapassar a quantidade que tem no estoque desse produto
	- [x] Adição ao carrinho mostra possíveis descontos caso haja um desconto bem próximo a ser aplicado para fazer o cliente comprar mais
		- [x] Caso o desconto seja aplicado induz o cliente a finalizar a compra
- [x] Página do carrinho
	- [x] O carrinho é feito pela sessão do PHP
	- [x] Caso um desconto esteja próximo de ser aplicado mostra na tela do cliente que se ele comprar mais ganhará um desconto para induzi-lo a gastar mais
	- [x] Mostra a quantidade de produtos
	- [x] Mostra o valor total da compra e também o valor total já com desconto caso tenha
	- [x] Mostra todos os produtos do carrinho
		- [x] Opção de adicionar/remover produto pela quantidade
		- [x] Opção de excluir produto do carrinho
- [x] Finalização de compra
	- [x]  Ao finalizar uma compra a quantidade do produto já é removida automaticamente do estoque e caso o produto esgote já marca automaticamente como indisponível.
- [x] Avisos pelo Telegram
	- [x] Erros inesperados com o banco de dados
	- [x] Pedidos novos, atendidos e excluídos
		- [x] Caso o pedido excluído com opção de retornar os produtos para o estoque é avisado quais produtos retornaram e sua nova quantidade.
	- [x] Produtos desativados por ter zerado o estoque

## Configurações necessárias

No arquivo `app/Db/Database.php` você deverá definir as variáveis `HOST, NAME, USER, PASS` para conectar ao banco de dados.

No arquivo `app/Telegram/Alert.php` você deverá definir a variável `TELEGRAM_BOT_TOKEN` com o token do seu robô do Telegram e `TELEGRAM_CHAT_ID` com o ID do chat do Telegram onde você deseja receber os avisos.

O arquivo `database.sql` contém toda a estrutura do banco de dados.

## Painel de controle
Para acessar o painel de controle vá para o diretório `/fdj/index.php` e insira o usuário `admin` e a senha `123`, altere o usuário e a senha para sua preferência.

## Importante
Caso você for utilizar esse projeto favor mudar o nome e tudo que esteja relacionado a `Fonte das Joias`, a logo, o link do Instagram que se encontra na maioria das páginas e do WhatsApp também pois a loja ainda existe e está em funcionamento ;).
