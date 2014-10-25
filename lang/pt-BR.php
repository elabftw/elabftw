<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
// GENERAL
define("YES", "Sim");
define("NO", "Não");
define("CONFIG_UPDATE_OK", "Configuração alterada.");
define("ERROR_BUG", "Uia! Um problema inesperado! Favor <a href='https://github.com/NicolasCARPi/elabftw/issues/'>abrir um issue no GitHub</a> se você acha que isto é um bug.");
define("INVALID_ID", "Parâmetro id inválido!");
define("INVALID_USERID", "Userid inválido.");
define("INVALID_TYPE", "Parâmetro type inválido!");
define("INVALID_FORMKEY", "Chave de formulário inválida. Favor tentar novamente.");
define("INVALID_EMAIL", "Endereço eletrônico inválido.");
define("INVALID_PASSWORD", "A senha não é essa!");
define("INVALID_USER", "Não há usuário com este e-mail na sua equipe");
define("PASSWORDS_DONT_MATCH", "Senhas não conferem!");
define("PASSWORD_TOO_SHORT", "A senha precisa ter pelo menos 8 caracteres.");
define("NEED_TITLE", "Cadê o título???");
define("NEED_PASSWORD", "Cadê a senha???");
define("FIELD_MISSING", "Os campos obrigatórios... são obrigatórios!");
define("NO_ACCESS_DIE", "Infelizmente você não pode acessar esta seção.");

define("USERNAME", "Usuário");
define("PASSWORD", "Senha");
define("FIRSTNAME", "Nome");
define("LASTNAME", "Sobrenome");
define("EMAIL", "E-mail");
define("TEAM", "Equipe");

define("DATE", "Data");
define("TITLE", "Título");
define("INFOS", "Informações");
define("VISIBILITY", "Visibilidade");
define("STATUS", "Situação");

define("EDIT", "Modificar");
define("SAVE", "Salvar");
define("SAVED", "Salvo!");
define("CANCEL", "Cancelar");
define("SAVING", "Salvando");
define("SAVE_AND_BACK", "Salvar e voltar");
define("UPDATED", "Atualizado!");

define("ACTION", "Ação");
define("SHORTCUT", "Atalho");
define("CREATE", "Criar");
define("SUBMIT", "Enviar");
define("TODO", "Lista de Tarefas");

define("NAME", "Nome");
define("PHONE", "Telefone");
define("MOBILE", "Celular");
define("WEBSITE", "Sítio internet");
define("SKYPE", "Skype");

// EMAILS
define("EMAIL_NEW_USER_SUBJECT", "[eLabFTW] Novo usuário registrado");
define("EMAIL_NEW_USER_BODY_1", "Ôpa,
    A sua conta no eLabFTW foi ativada. Agora você pode se conectar :");
define("EMAIL_SEND_ERROR", "Houve um problema com o envio do e-mail! Este erro foi registrado.");
define("EMAIL_SUCCESS", "Correio eletrônico enviado. Verifique sua caixa de entrada.");
define("EMAIL_FOOTER", "

~~~
Correio eletrônico enviado por eLabFTW
http://www.elabftw.net
O caderno de laboratório eletrônico grátis e de códio aberto");

// ADMIN.PHP
define("ADMIN_TITLE", "Painel de administração");

define("ADMIN_VALIDATION_QUEUE", "Há usuários pedindo validação de conta:");
define("ADMIN_VALIDATION_QUEUE_SUBMIT", "Validar a(s) conta(s) de");

define("ADMIN_MENU_TEAM", "Equipe");
define("ADMIN_MENU_USERS", "Usuários");
define("ADMIN_MENU_ITEMSTYPES", "Tipos de itens");
define("ADMIN_MENU_EXPTPL", "Modelo de Experimento");
define("ADMIN_MENU_CSV", "Importar arquivo CSV");

define("ADMIN_TEAM_H3", "Configure a sua equipe");
define("ADMIN_TEAM_DELETABLE_XP", "Usuários podem apagar experimentos?");
define("ADMIN_TEAM_LINK_NAME", "Nome no menu superior:");
define("ADMIN_TEAM_LINK_HREF", "Endereço para o qual este link deve apontar:");
define("ADMIN_TEAM_STAMPLOGIN", "Login do serviço de timestamping:");
define("ADMIN_TEAM_STAMPLOGIN_HELP", "Este tem que ser o endereço e-mail associado à sua conta Universign.com.");
define("ADMIN_TEAM_STAMPPASS", "Senha do serviço de timestamping:");
define("ADMIN_TEAM_STAMPPASS_HELP", "Sua senha Universign");

define("ADMIN_USERS_H3", "Modificar usuários");
define("ADMIN_USERS_VALIDATED", "Tem conta ativa?");
define("ADMIN_USERS_GROUP", "Grupo:");
define("ADMIN_USERS_RESET_PASSWORD", "Modificar a senha deste usuário:");
define("ADMIN_USERS_REPEAT_PASSWORD", "Repita a nova senha:");
define("ADMIN_USERS_BUTTON", "Modificar este usuário");

define("ADMIN_DELETE_USER_H3", "ZONA PERIGOSA");
define("ADMIN_DELETE_USER_H4", "Apagar uma conta");
define("ADMIN_DELETE_USER_HELP", "Digite o ENDEREÇO E-MAIL de um usuário para apagar tudo relacionado a ele (inclusive experimentos e arquivos) pra sempre:");
define("ADMIN_DELETE_USER_CONFPASS", "Digite sua senha:");
define("ADMIN_DELETE_USER_BUTTON", "Apagar este usuário!");

define("ADMIN_STATUS_ADD_H3", "Adicionar uma situação possível");
define("ADMIN_STATUS_ADD_NEW", "Nome da nova situação:");
define("ADMIN_STATUS_ADD_BUTTON", "Adicionar situação");
define("ADMIN_STATUS_EDIT_H3", "Modificar uma situação existente");
define("ADMIN_STATUS_EDIT_ALERT", "Remova todos os experimentos nesta situação antes de apagá-la.");
define("ADMIN_STATUS_EDIT_DEFAULT", "Situação dos novos experimentos");

define("ADMIN_ITEMS_TYPES_H3", "Tipos de itens do banco de dados");
define("ADMIN_ITEMS_TYPES_ALERT", "Remova todos os itens deste tipo antes de apagar o tipo.");
define("ADMIN_ITEMS_TYPES_EDIT_NAME", "Modificar o nome:");
define("ADMIN_ITEMS_TYPES_ADD", "Adicionar um tipo de item:");
define("ADMIN_ITEMS_TYPES_ADD_BUTTON", "Adicionar tipo de item");

define("ADMIN_EXPERIMENT_TEMPLATE_H3", "Modelo de experimento padrão");
define("ADMIN_EXPERIMENT_TEMPLATE_HELP", "Isto vai aparecer sempre que alguém criar um novo experimento.");

define("ADMIN_IMPORT_CSV_H3", "Importar um arquivo CSV");
define("ADMIN_IMPORT_CSV_HELP", "Esta página permite a importação de um arquivo .csv (planilha Excel) no banco de dados.<br>Primeiro você precisa abrir o seu arquivo .xls ou .xlsx no Excel ou no Libreoffice e então salvá-lo como .csv.<br>Pra isto aqui funcionar direito, a primeira coluna tem que ser o título. As outras colunas serão importadas no corpo do item. É melhor testar a importação com umas 3 ou 4 linhas só pra ver se funciona antes de importar um arquivo muito grande.");
define("ADMIN_IMPORT_CSV_HELP_STRONG", "É melhor gravar uma cópia de segurança do seu banco de dados antes de importar uma tabela muito grande!");
define("ADMIN_IMPORT_CSV_STEP_1", "1. Selecione o tipo que esses itens terão:");
define("ADMIN_IMPORT_CSV_STEP_2", "2. Selecione um arquivo CSV a importar:");
define("ADMIN_IMPORT_CSV_BUTTON", "Importar CSV");
define("ADMIN_IMPORT_CSV_MSG", "Consegui importar tudo.");

// ADMIN-EXEC.PHP
define("ADMIN_USER_VALIDATED", "Validou a conta de :");
define("ADMIN_TEAM_ADDED", "A equipe foi adicionada.");
define("SYSADMIN_GRANT_SYSADMIN", "Pra tornar alguém um administrador do sistema, você também tem que ser administrador do sistema.");
define("USER_DELETED", "Tudo limpo.");

// CHANGE-PASS.PHP
define("CHANGE_PASS_TITLE", "Modificar senha");
define("CHANGE_PASS_PASSWORD", "Nova senha");
define("CHANGE_PASS_REPEAT_PASSWORD", "Digite de novo, por favor");
define("CHANGE_PASS_HELP", "mínimo de 8 caracteres");
define("CHANGE_PASS_COMPLEXITY", "Complexidade");
define("CHANGE_PASS_BUTTON", "Gravar nova senha");
// this is in JS code, so probably not a good idea to put ' or "
define("CHANGE_PASS_WEAK", "Senha fraquinha");
define("CHANGE_PASS_AVERAGE", "Mais ou menos");
define("CHANGE_PASS_GOOD", "Boazinha essa senha");
define("CHANGE_PASS_STRONG", "Senha boa pra caramba");
define("CHANGE_PASS_NO_WAY", "Ah, vai, tem certeza de que vai lembrar disso???");

// CHECK_FOR_UPDATES.PHP
define("CHK_UPDATE_GIT", "Instale o git pra poder verificar as atualizações.");
define("CHK_UPDATE_CURL", "Você precisa instalar a extensão curl para php.");
define("CHK_UPDATE_UNKNOWN", "Branch desconhecido!");
define("CHK_UPDATE_GITHUB", "Desculpe, não consegui conectar no github.com pra ver se há atualizações.");
define("CHK_UPDATE_NEW", "Há uma nova atualização disponível!");
define("CHK_UPDATE_MASTER", "Parabéns! Você está rodando a mais recente versão estável do eLabFTW :)");
define("CHK_UPDATE_NEXT", "Parabéns! Você está rodando a mais recente versão de desenvolvimento do eLabFTW :)");

// CREATE_ITEM.PHP
define("CREATE_ITEM_WRONG_TYPE", "Tipo de item errado!");
define("CREATE_ITEM_UNTITLED", "Sem título");
define("CREATE_ITEM_SUCCESS", "Novo item criado.");

// DATABASE.PHP
define("DATABASE_TITLE", "Banco de Dados");

// DELETE_FILE.PHP
define("DELETE_FILE_FILE", "Arquivo");
define("DELETE_FILE_DELETED", "Apagado");

// DELETE.PHP
define("DELETE_NO_RIGHTS", "Você não tem permissão para apagar este experimento.");
define("DELETE_EXP_SUCCESS", "O experimento foi apagado.");
define("DELETE_TPL_SUCCESS", "O modelo foi apagado.");
define("DELETE_ITEM_SUCCESS", "O item foi apagado.");
define("DELETE_ITEM_TYPE_SUCCESS", "O tipo de item foi apagado.");
define("DELETE_STATUS_SUCCESS", "A situação foi apagada.");

// DUPLICATE_ITEM.PHP
define("DUPLICATE_EXP_SUCCESS", "O experimento foi duplicado.");
define("DUPLICATE_ITEM_SUCCESS", "Entrada de banco de dados duplicada.");

// EXPERIMENTS.PHP
define("EXPERIMENTS_TITLE", "Experimentos");

// LOCK.PHP
define("LOCK_NO_RIGHTS", "Você não tem autoridade para bloquear ou liberar isto aqui.");
define("LOCK_LOCKED_BY", "Este experimento foi bloqueado por");
define("LOCK_NO_EDIT", "Uma vez que o experimento foi marcado com timestamp, ele não pode ser desbloqueado nem modificado de forma alguma.");

// LOGIN-EXEC.PHP
define("LOGIN_FAILED", "Falha no Login. Ou a senha está incorreta, ou a sua conta ainda não está ativa.");

// LOGIN.PHP
define("LOGIN", "Login");
define("LOGIN_TOO_MUCH_FAILED", "Bixo, você tentou se logar tantas vezes que agora vai ficar de castigo.");
define("LOGIN_ATTEMPT_NB", "Número de tentativas de login antes de ser banido(a) por");
define("LOGIN_MINUTES", "minutos:");
// in JS code
define("LOGIN_ENABLE_COOKIES", "Favor habilitar os cookies no seu navegador.");
define("LOGIN_COOKIES_NOTE", "Nota: você precisa habilitar os cookies pra poder se conectar.");
define("LOGIN_H2", "Conecte-se à sua conta");
define("LOGIN_FOOTER", "Não tem uma conta ? <a href='register.php'>Registre-se</a> now!<br>Esqueceu a senha? <a href='#' class='trigger'>Modificar</a> a senha !");
define("LOGIN_FOOTER_PLACEHOLDER", "Digite seu endereço eletrônico");
define("LOGIN_FOOTER_BUTTON", "Enviar nova senha");

// MAKE_CSV.PHP
define("CSV_TITLE", "Exportar em forma de planilha");
define("CSV_READY", "Seu arquivo CSV está pronto:");

// MAKE_ZIP.PHP
define("ZIP_TITLE", "Comprimir em um arquivo zip");
define("ZIP_READY", "Seu arquivo zip está pronto:");

// PROFILE.PHP
define("PROFILE_TITLE", "Perfil");
define("PROFILE_EXP_DONE", "Experimentando com eLabFTW desde");

// REGISTER-EXEC.PHP
define("REGISTER_USERNAME_USED", "Já existe um usuário com esse nome!");
define("REGISTER_EMAIL_USED", "Alguém já está usando esse endereço eletrônico!");
define("REGISTER_EMAIL_BODY", "Ôpa,
Alguém acabou de abrir uma nova conta no eLabFTW. Vá para o painel de administrador para ativá-la!");
define("REGISTER_EMAIL_FAILED", "Não consegui enviar o e-mail para informar o administrador. Este problema foi registrado. Contate o administrador e peça para que ele valide a sua conta.");
define("REGISTER_SUCCESS_NEED_VALIDATION", "Registro bem-sucedido :)<br>Agora a sua conta precisa ser validada por um administrador.<br>Você deve receber um e-mail avisando que ela foi ativada.");
define("REGISTER_SUCCESS", "Registro bem-sucedido :)<br>Bem-vindo(a) ao eLabFTW \o/");

// REGISTER.PHP
define("REGISTER_TITLE", "Registrar");
define("REGISTER_LOGOUT", "Favor <a style='alert-link' href='logout.php'>se desconectar</a> para poder registrar uma outra conta.");
define("REGISTER_BACK_TO_LOGIN", "de volta à página de login");
define("REGISTER_H2", "Crie sua conta");
define("REGISTER_DROPLIST", "------ Selecione uma equipe ------");
define("REGISTER_CONFIRM_PASSWORD", "Confirme a senha");
define("REGISTER_PASSWORD_COMPLEXITY", "Complexidade da senha");
define("REGISTER_BUTTON", "criar");

// RESET-EXEC.PHP
define("RESET_SUCCESS", "Senha atualizada. Agora você pode se conectar.");

// RESET-PASS.PHP
define("RESET_MAIL_SUBJECT", "[eLabFTW] Modificação de senha");
define("RESET_MAIL_BODY", "Olá,
Alguém (esperamos que tenha sido você) com o endereço IP:");
define("RESET_MAIL_BODY2", "e agente de usuário:");
define("RESET_MAIL_BODY3", "pediu uma nova senha no eLabFTW.

Siga este link se quiser realmente mudar a sua senha:");
define("RESET_NOT_FOUND", "Endereço eletrônico não encontrado no banco de dados!");

// REVISIONS.PHP
define("REVISIONS_TITLE", "Revisões");
define("REVISIONS_GO_BACK", "Voltar aos experimentos");
define("REVISIONS_LOCKED", "Não se pode voltar atrás num experimento bloqueado!");
define("REVISIONS_CURRENT", "Versão Atual:");
define("REVISIONS_SAVED", "Gravada em:");
define("REVISIONS_RESTORE", "Restaurar");

// SEARCH.PHP
define("SEARCH", "Busca");
define("SEARCH_TITLE", "Busca com mais opções");
define("SEARCH_BACK", "Voltar para a lista de experimentos");
define("SEARCH_ONLY", "Busque somente nos experimentos de:");
define("SEARCH_YOU", "Você mesmo(a)");
define("SEARCH_EVERYONE", "Busque nos experimentos de todos");
define("SEARCH_IN", "Busque em");
define("SEARCH_DATE", "Com data entre");
define("SEARCH_AND", "e");
define("SEARCH_AND_TITLE", "Título contém");
define("SEARCH_AND_BODY", "Corpo de texto contém");
define("SEARCH_AND_STATUS", "Situação é");
define("SEARCH_SELECT_STATUS", "selecione a situação");
define("SEARCH_AND_RATING", "Com nota");
define("SEARCH_STARS", "selecione o número de estrelas");
define("SEARCH_UNRATED", "Sem estrela");
define("SEARCH_BUTTON", "Buscar");
define("SEARCH_EXPORT", "Exportar estes resultados:");
define("SEARCH_SORRY", "Desculpa aí, não achei nada :(");

// SYSCONFIG.PHP
define("SYSCONFIG_TITLE", "configuração do eLabFTW");
define("SYSCONFIG_TEAMS", "Equipes");
define("SYSCONFIG_SERVER", "Servidor");
define("SYSCONFIG_TIMESTAMP", "Timestamp");
define("SYSCONFIG_SECURITY", "Segurança");
define("SYSCONFIG_1_H3_1", "Adicionar equipe");
define("SYSCONFIG_1_H3_2", "Modificar equipes existentes");
define("SYSCONFIG_MEMBERS", "Membros");
define("SYSCONFIG_ITEMS", "Itens");
define("SYSCONFIG_CREATED", "Criação");
define("SYSCONFIG_2_H3", "Under the hood");
define("SYSCONFIG_DEBUG", "Ativar modo debug:");
define("SYSCONFIG_DEBUG_HELP", "Quando ativo, o conteúdo de \$_SESSION e de \$_COOKIES será mostrado no rodapé para os adminsitradores.");
define("SYSCONFIG_PROXY", "Endereço do proxy:");
define("SYSCONFIG_PROXY_HELP", "Se você está atrás de uma fireway ou de um proxy, digite o endereço. Exemplo : http://proxy.exemplo.com.br:3128");
define("SYSCONFIG_PATH", "Endereço completo do diretório de instalação:");
define("SYSCONFIG_PATH_HELP", "Na verdade este é o hash md5 do caminho para a instalação. Você provavelmente não precisa mudá-lo a menos que esteja movendo uma instalação existente.");
define("SYSCONFIG_3_H3", "Configuração do timestamping Universign");
define("SYSCONFIG_STAMPSHARE", "As equipes podem usar as credenciais abaixo para o timestamp:");
define("SYSCONFIG_STAMPSHARE_HELP", "Você pode controlar o uso da conta global Universign pelas equipes. Se colocado em <em>não</em>, o administrador de equipe precisa adicionar informações de login no painel de administração.");
define("SYSCONFIG_STAMPLOGIN", "Login para o serviço externo de timestamping:");
define("SYSCONFIG_STAMPLOGIN_HELP", "Isto tem que ser um endereço eletrônico.");
define("SYSCONFIG_STAMPPASS", "Senha para o serviço externo de timestamping:");
define("SYSCONFIG_STAMPPASS_HELP", "Atenção: este senha será gravada de forma não criptografada no banco de dados! Use uma senha que não abra outras portas…");
define("SYSCONFIG_4_H3", "Configurações de Segurança");
define("SYSCONFIG_ADMIN_VALIDATE", "Os usuários precisam ser autorizados pelo administrador após registro:");
define("SYSCONFIG_ADMIN_VALIDATE_HELP", "Deixe em sim se quiser ter mais segurança.");
define("SYSCONFIG_LOGIN_TRIES", "Número de tentativas de conexão que o usuário pode fazer:");
define("SYSCONFIG_LOGIN_TRIES_HELP", "3 pode ser pouco demais. Cuidado :)");
define("SYSCONFIG_BAN_TIME", "Tempo de espera após tantas tentativas de conexão fracassadas (em minutos):");
define("SYSCONFIG_BAN_TIME_HELP", "Para poder identificar um usuário, usamos um md5 do agente de usuário e também seu IP. Porque se fizéssemos isso apenas baseado em IP, certamente causaríamos problemas.");
define("SYSCONFIG_5_H3", "Configuração SMTP");
define("SYSCONFIG_5_HELP", "Se o sistema não tiver uma maneira eficaz de enviar e-mails, os usuários não poderão modificar suas senhas. Recomendamos que você crie uma conta exclusiva no Mandrill.com (ou então no Gmail) e adicione as informações de conexão aqui.");
define("SYSCONFIG_SMTP_ADDRESS", "Endereço do servidor SMTP:");
define("SYSCONFIG_SMTP_ADDRESS_HELP", "smtp.mandrillapp.com");
define("SYSCONFIG_SMTP_ENCRYPTION", "Criptografia SMTP (pode ser TLS ou STARTSSL):");
define("SYSCONFIG_SMTP_ENCRYPTION_HELP", "Aqui pode deixar TLS sem problemas.");
define("SYSCONFIG_SMTP_PORT", "Porta SMTP:");
define("SYSCONFIG_SMTP_PORT_HELP", "A configuração normal é 587.");
define("SYSCONFIG_SMTP_USERNAME", "Usuário SMTP:");
define("SYSCONFIG_SMTP_PASSWORD", "Senha SMTP:");

// TEAM.PHP
define("TEAM_TITLE", "Equipe");
define("TEAM_STATISTICS", "Estatísticas");
define("TEAM_TIPS_TRICKS", "Dicas de uso");
define("TEAM_BELONG", "Você pertence à equipe");
define("TEAM_TEAM", ".");
define("TEAM_TOTAL_OF", "Há um total de");
define("TEAM_EXP_BY", "experimentos pertencentes a");
define("TEAM_DIFF_USERS", "usuários diferentes.");
define("TEAM_ITEMS_DB", "itens no banco de dados.");
define("TEAM_TIP_1", "Para exibir a Lista de Tarefas, pressione 't'");
define("TEAM_TIP_2", "Você pode definir modelos de experimentos (<a href='ucp.php?tab=3'>Painel de Controle</a>)");
define("TEAM_TIP_3", "O administrador da equipe pode modificar a lista de situações e os tipos disponíveis de itens(<a href='admin.php?tab=4'>Painel de Administração</a>)");
define("TEAM_TIP_4", "Se você pressionar Control+Shift+D no editor de texto, a data de hoje será adicionada na posição em que o cursor estiver");
define("TEAM_TIP_5", "Você pode programar seus próprios atalhos (<a href='ucp.php?tab=2'>Painel de Controle</a>)");
define("TEAM_TIP_6", "Com um clique, você duplica um experimento");
define("TEAM_TIP_7", "Clique numa etiqueta para listar todos os itens que a usam");
define("TEAM_TIP_8", "Registrar uma conta no <a href='https://www.universign.eu/en/timestamp'>Universign</a> para poder registrar timestamping nos experimentos");
define("TEAM_TIP_9", "Bloqueie o experimento para poder gravar o timestamp");
define("TEAM_TIP_10", "CUIDADO: uma vez gravado o timestamp, o experimento não poderá ser desbloqueado nem modificado. Apenas comentários poderão ser adicionados.");

// TIMESTAMP.PHP
define("TIMESTAMP_CONFIG_ERROR", "O timestamping ainda não foi configurado. Favor dar uma olhada na <a class='alert-link' href='https://github.com/NicolasCARPi/elabftw/wiki/finalizing#setting-up-timestamping'>wiki</a>.");
define("TIMESTAMP_ERROR", "Houve um erro na tentativa de timestamping. Provavelmente as credenciais de login não estão corretas, ou então acabaram os seus créditos.");
define("TIMESTAMP_USER_ERROR", "Houve um erro na tentativa de timestamping. Este experimento NÃO foi gravado com timestamp. Este problema foi registrado.");
define("TIMESTAMP_SUCCESS", "O experimento foi gravado com timestamp. O PDF com timestamp pode ser baixado com a partir deste link.");

// UCP-EXEC.PHP
define("UCP_TITLE", "Painel de Controle do Usuário");
define("UCP_PASSWORD_SUCCESS", "A senha foi atualizada!");
define("UCP_PROFILE_UPDATED", "O perfil foi atualizado!");
define("UCP_ENTER_PASSWORD", "Digite sua senha para poder modificar algo!");
define("UCP_PREFS_UPDATED", "Suas preferências foram atualizadas.");
define("UCP_TPL_NAME", "Favor especificar um nome para o modelo!");
define("UCP_TPL_SHORT", "O nome do modelo precisa ter no mínimo 3 caracteres.");
define("UCP_TPL_SUCCESS", "O modelo de experimento foi criado.");
define("UCP_TPL_EDITED", "O modelo de experimento foi modificado.");
define("UCP_TPL_PLACEHOLDER", "Nome do modelo");
define("UCP_ACCOUNT", "Conta");
define("UCP_PREFERENCES", "Preferências");
define("UCP_TPL", "Modelos");
define("UCP_H4_1", "Modifique suas informações pessoais");
define("UCP_H4_2", "Modifique sua identidade");
define("UCP_H4_3", "Modifique sua senha");
define("UCP_NEWPASS", "Nova senha");
define("UCP_CNEWPASS", "Confirme a nova senha");
define("UCP_H4_4", "Modifique suas informações para contato");
define("UCP_BUTTON_1", "Atualize o perfil");
define("UCP_H3_1", "APARENCIA");
define("UCP_DEFAULT", "Normal");
define("UCP_COMPACT", "Compacto");
define("UCP_ORDER_BY", "Ordenar por:");
define("UCP_ITEM_ID", "ID do item");
define("UCP_WITH", "com");
define("UCP_NEWER", "primeiro os mais recentes");
define("UCP_OLDER", "primeiro os mais antigos");
define("UCP_LIMIT", "Itens por página:");
define("UCP_H3_2", "ATALHOS DE TECLADO");
define("UCP_H3_3", "ALERTA");
define("UCP_CLOSE_WARNING", "Perguntar antes de fechar uma janela de edição ?");
define("UCP_CREATE_NEW", "Criar nova");
define("UCP_ADD_TPL", "Criar um modelo");
define("UCP_EDIT_BUTTON", "Modificar modelo");
define("LANGUAGE", "Idioma");

// VIEW DB
define("NOTHING_TO_SHOW", "Não existe nada com esse ID.");
define("LAST_MODIFIED_BY", "Modificado pela última vez por");
define("ON", "em");

// VIEW XP
define("VIEW_XP_FORBIDDEN", "<strong>Acesso proibido:</strong> a configuração de visibilidade deste experimento é 'somente para o proprietário'.");
define("VIEW_XP_RO", "<strong>Modo somente leitura:</strong> este experimento pertence a");
define("VIEW_XP_TIMESTAMPED", "O registro de timestamp foi feito por");
define("AT", "em");
define("VIEW_XP_ELABID", "eLabID único:");
define("COMMENTS", "Comentários");
define("ADD_COMMENT", "Clique para adicionar um comentário");
define("DELETE_THIS", "Apagar isto?"); // in JS
define("CONFIRM_STAMP", "Uma vez que o timestamp for feito, o experimento não poderá ser modificado. Isto é definitivo! Tem certeza que quer gravar o timestamp agora ?"); // in JS

// TAGCLOUD
define("TAGCLOUD_H4", "Nuvem de Etiquetas");
define("NOT_ENOUGH_TAGS", "Não existem etiquetas suficientes pra construir uma nuvem.");

// STATISTICS
define("STATISTICS_H4", "Estatísticas");
define("STATISTICS_NOT_YET", "Ainda não há estatísticas disponíveis.");
define("STATISTICS_EXP_FOR", "Experimentos de");

// FILE UPLOAD
define("FILE_UPLOAD_H3", "Anexar um arquivo.");
define("FILE_START_UPLOAD", "Iniciar upload");

// SHOW DB
define("SHOW_DB_CREATE_NEW", "CRIAR NOVO");
define("SHOW_DB_FILTER_TYPE", "FILTRAR POR TIPO");
define("FOUND", "Encontrado(s)");
define("RESULTS", "resultados");
define("FOUND_1", "Encontrado 1 resultado.");
define("FOUND_0", "Não encontrei nada.");
define("SHOW_DB_WELCOME", "<strong>Bem-vindo(a) ao eLabFTW.</strong>Selecione um item na lista «Criar novo» para começar a alimentar o banco de dados.");
define("SHOW_DB_LAST_10", "Mostrando 10 últimos itens gravados:");

// SHOW XP
define("SHOW_XP_MORE", "Carregar mais");
define("SHOW_XP_CREATE", "Novo experimento");
define("SHOW_XP_CREATE_TPL", "Novo a partir de um modelo");
define("SHOW_XP_FILTER_STATUS", "FILTRAR POR SITUAÇÃO");
define("SHOW_XP_NO_TPL", "<strong>Você ainda não tem nenhum modelo.</strong> Vá para o <a  class='alert-link' href='ucp.php?tab=3'>seu painel de controle</a> para fazer um !");
define("SHOW_XP_NO_EXP", "<strong>Bem-vindo(a) ao eLabFTW.</strong>Clique em <img src='img/add.png' alt='Criar experimento' /><a class='alert-link' href='create_item.php?type=exp'>Criar experimento</a> para começar.");

// EDIT DB
define("LOCKED_NO_EDIT", "<strong>Este item está bloqueado.</strong> Você não pode modificá-lo.");
define("TAGS", "Etiquetas");
define("CLOSE_WARNING", "Você quer mesmo sair desta página? As modificações não salvas serão perdidas!");

// EDIT XP
define("EDIT_XP_NO_RIGHTS", "<strong>Você não pode modificar:</strong> este experimento não é seu!");
define("EDIT_XP_TAGS_HELP", "clique em uma etiqueta para removê-la");
define("EDIT_XP_ADD_TAG", "Adicionar uma etiqueta");
define("ONLY_THE_TEAM", "Apenas a equipe");
define("ONLY_ME", "só me");
define("EXPERIMENT", "Experimento");
define("LINKED_ITEMS", "Itens conectados");
define("ADD_LINK", "Conectar um item");
define("ADD_LINK_PLACEHOLDER", "no banco de dados");
define("SHOW_HISTORY", "Mostrar histórico");
define("REVISION_AVAILABLE", "revisão disponível.");
define("REVISIONS_AVAILABLE", "revisões disponíveis.");
define("SHOW_HISTORY", "Show de história");

// INC/HEAD.PHP
define("LOGGED_IN_AS", "Oi,");
define("SETTINGS", "Configurações");
define("LOGOUT", "Desconectar-se");

// INC/FOOTER.PHP
define("CHECK_FOR_UPDATES", "VERIFICAR ATUALIZAÇÕES DISPONÍVEIS");
define("SYSADMIN_PANEL", "PAINEL DO ADMINISTRADOR DE SISTEMAS");
define("ADMIN_PANEL", "PAINEL DO ADMINISTRADOR");
define("POWERED_BY", "Turbinado por");
define("PAGE_GENERATED", "Esta página foi gerada em");
