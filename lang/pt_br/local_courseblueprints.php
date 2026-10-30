<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings.
 *
 * @package    local_courseblueprints
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['archive'] = 'Arquivar';
$string['back'] = 'Voltar';
$string['backupfailed'] = 'Backup do Moodle não retornou um arquivo válido.';
$string['backupfile'] = 'Arquivo Moodle .mbz (se necessário)';
$string['backupmissing'] = 'O arquivo do modelo não foi encontrado.';
$string['batch'] = 'Cursos em lote';
$string['batchhelp'] = 'Opcional: um curso por linha em Nome completo|Nome breve (máximo 100). Deixe vazio para criar um curso.';
$string['batchnumber'] = 'Lote nº {$a}';
$string['catalogue'] = 'Modelos institucionais';
$string['category'] = 'Categoria de destino';
$string['chooseversion'] = 'Versão publicada do modelo';
$string['code'] = 'Código estável';
$string['compare'] = 'Comparar';
$string['comparisoninformative'] = 'Este relatório é apenas informativo e nunca altera um curso.';
$string['confirmprovision'] = 'Confirmar provisionamento';
$string['courseblueprints:create'] = 'Criar modelos';
$string['courseblueprints:provision'] = 'Provisionar cursos a partir de modelos';
$string['courseblueprints:publish'] = 'Revisar, publicar e arquivar versões dos modelos';
$string['courseblueprints:view'] = 'Visualizar modelos institucionais e histórico';
$string['courseformat'] = 'Formato de curso';
$string['createblueprint'] = 'Criar modelo';
$string['createddraft'] = 'Novo rascunho criado; o backup do curso de origem foi agendado, quando aplicável.';
$string['dependencies'] = 'Dependências / componentes não suportados';
$string['dependenciesok'] = 'OK';
$string['description'] = 'Descrição';
$string['duplicatebatchshortnames'] = 'Há nomes breves repetidos no lote.';
$string['duplicatecode'] = 'Este código de modelo já está em uso.';
$string['emptycatalogue'] = 'Ainda não foram criados modelos institucionais.';
$string['enddate'] = 'Data de término (opcional)';
$string['enddatelabel'] = 'Data de término';
$string['error'] = 'Erro';
$string['eventprovisioned'] = 'Curso provisionado a partir de modelo';
$string['eventpublished'] = 'Revisão do modelo publicada';
$string['export'] = 'Exportar JSON';
$string['fullname'] = 'Nome completo do curso';
$string['history'] = 'Histórico de provisionamento';
$string['idempotencyconflict'] = 'Esta chave de solicitação pertence a outra operação.';
$string['immutableversion'] = 'Versões publicadas ou arquivadas não podem ser editadas.';
$string['import'] = 'Importar';
$string['importedblueprint'] = 'Modelo importado';
$string['importinvalid'] = 'Importação inválida: {$a}';
$string['integrityfailure'] = 'Falha de integridade: o arquivo do modelo foi alterado.';
$string['invalidbatchrow'] = 'Cada linha do lote deve conter NOME COMPLETO|NOME BREVE.';
$string['invalidbatchsize'] = 'O lote deve conter de 1 a 100 cursos.';
$string['invalidblueprintidentity'] = 'O código e o nome do modelo são obrigatórios.';
$string['invaliddates'] = 'A data final não pode ser anterior à data inicial.';
$string['invalidjson'] = 'Objeto JSON inválido.';
$string['invalidpreview'] = 'A prévia expirou ou não pertence a esta sessão.';
$string['invalidprovisionparams'] = 'Parâmetros de provisionamento inválidos.';
$string['invalidrequestkey'] = 'Chave de solicitação ou quantidade de cursos inválida.';
$string['invalidreviewrecord'] = 'É obrigatório registrar a revisão manual.';
$string['invalidsections'] = 'Selecione entre 1 e 52 seções.';
$string['invalidversion'] = 'Versão de modelo inválida para esta operação.';
$string['lockfailed'] = 'A operação já está em andamento ou não pôde ser bloqueada.';
$string['manifestfile'] = 'Manifesto JSON do modelo';
$string['missingdependencies'] = 'Há componentes obrigatórios ausentes ou não suportados.';
$string['name'] = 'Nome';
$string['namesrequired'] = 'Informe o nome completo e breve, ou os cursos em lote.';
$string['newrevision'] = 'Nova revisão';
$string['next'] = 'Próximo';
$string['nodifferences'] = 'Nenhuma divergência encontrada no escopo estrutural suportado.';
$string['nopublished'] = 'Não existem versões publicadas disponíveis para provisionamento.';
$string['orphancoursereview'] = 'Já existe um curso com este identificador de provisionamento; faça a conciliação manualmente.';
$string['partialcoursereview'] = 'Um curso já provisionado exige revisão manual; a restauração automática foi desativada.';
$string['pluginname'] = 'Modelos institucionais de cursos';
$string['preview'] = 'Prévia do provisionamento';
$string['privacy:metadata:authorid'] = 'O criador da revisão do modelo.';
$string['privacy:metadata:blueprint'] = 'Modelo institucional com responsável associado.';
$string['privacy:metadata:ownerid'] = 'O responsável pelo modelo.';
$string['privacy:metadata:reviewerid'] = 'O revisor que aprovou uma revisão do modelo.';
$string['privacy:metadata:reviewnotes'] = 'Anotações de revisão de privacidade.';
$string['privacy:metadata:run'] = 'Solicitante do provisionamento em lote.';
$string['privacy:metadata:userid'] = 'O usuário que solicitou o provisionamento.';
$string['privacy:metadata:version'] = 'Autoria da versão e anotações de revisão.';
$string['provision'] = 'Provisionar cursos';
$string['publish'] = 'Publicar';
$string['queued'] = 'As tarefas de provisionamento foram agendadas.';
$string['restorefailed'] = 'Falha na restauração nativa do Moodle.';
$string['review'] = 'Revisar';
$string['reviewed'] = 'Inspecionei manualmente todos os materiais do curso e confirmei que nenhum dado pessoal ou conteúdo não suportado será publicado.';
$string['reviewnotes'] = 'Evidências da revisão de privacidade e conteúdo';
$string['reviewrequired'] = 'É necessária uma revisão manual de privacidade registrada antes da publicação.';
$string['reviewwarning'] = 'A verificação automática não oferece garantia. Revise todas as atividades, arquivos, questões, recursos e HTML elaborado antes de aprovar.';
$string['revision'] = 'Revisão';
$string['revisionimportunsupported'] = 'Uma versão importada com arquivo .mbz não pode gerar revisão sem recapturar a origem; faça uma nova importação.';
$string['revisions'] = 'Revisões';
$string['runstatuscomplete'] = 'Concluído';
$string['runstatusfailedreview'] = 'Falha: precisa de revisão manual';
$string['runstatusneedsreview'] = 'Precisa de revisão';
$string['runstatusprocessing'] = 'Em processamento';
$string['runstatusqueued'] = 'Na fila';
$string['savereview'] = 'Registrar revisão';
$string['sections'] = 'Número de seções';
$string['shortname'] = 'Nome breve do curso';
$string['shortnameexists'] = 'O nome breve já está em uso por outro curso Moodle.';
$string['sourceblank'] = 'Configurações iniciais';
$string['sourcecourseid'] = 'ID do curso de origem';
$string['sourceexisting'] = 'Curso Moodle existente';
$string['sourcemode'] = 'Origem do modelo';
$string['sourcestale'] = 'O curso de origem mudou após a captura da revisão. Crie e revise uma nova revisão.';
$string['startdate'] = 'Data de início';
$string['startdatelabel'] = 'Data de início';
$string['status'] = 'Estado';
$string['taskcapture'] = 'Capturar backup do modelo';
$string['taskprovision'] = 'Provisionar curso a partir de modelo';
$string['unexpectedbackup'] = 'Foi enviado um arquivo de backup, mas o manifesto não exige esse arquivo.';
$string['unpublishedversion'] = 'Esta versão não está disponível para novos provisionamentos.';
$string['versionid'] = 'ID da versão {$a}';
$string['versionstatusarchived'] = 'Arquivado';
$string['versionstatusdraft'] = 'Rascunho';
$string['versionstatuspublished'] = 'Publicado';
