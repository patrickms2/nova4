-- -------------------------------------------------------------
-- TablePlus 26.7.3(739)
--
-- https://tableplus.com/
--
-- Database: mcp
-- Generation Time: 2026-07-12 06:50:54.7260
-- -------------------------------------------------------------


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


DROP TABLE IF EXISTS `bot_access_tokens`;
CREATE TABLE `bot_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `owner_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash_version` tinyint unsigned NOT NULL DEFAULT '1',
  `token_prefix` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowed_areas` json DEFAULT NULL,
  `abilities` json DEFAULT NULL,
  `rate_limit_per_minute` int unsigned DEFAULT NULL,
  `max_input_tokens` bigint unsigned DEFAULT NULL,
  `max_output_tokens` bigint unsigned DEFAULT NULL,
  `monthly_token_budget` bigint unsigned DEFAULT NULL,
  `monthly_cost_budget_cents` bigint unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bot_access_tokens_token_hash_unique` (`token_hash`),
  KEY `bot_access_tokens_bot_id_is_active_index` (`bot_id`,`is_active`),
  KEY `bot_access_tokens_token_prefix_index` (`token_prefix`),
  KEY `bot_access_tokens_expires_at_index` (`expires_at`),
  KEY `bot_access_tokens_is_active_index` (`is_active`),
  KEY `bot_access_tokens_revoked_at_index` (`revoked_at`),
  KEY `bot_access_tokens_owner_index` (`owner_type`,`owner_id`),
  KEY `bot_access_tokens_created_by_index` (`created_by_type`,`created_by_id`),
  KEY `bot_access_tokens_channel_index` (`channel`),
  KEY `bot_access_tokens_bot_channel_index` (`bot_id`,`channel`),
  CONSTRAINT `bot_access_tokens_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_action_executions`;
CREATE TABLE `bot_action_executions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `bot_conversation_id` bigint unsigned DEFAULT NULL,
  `workflow_run_id` bigint unsigned DEFAULT NULL,
  `action_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'running',
  `result` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bot_action_executions_business_unique` (`bot_conversation_id`,`workflow_run_id`,`action_key`,`business_key`),
  KEY `bot_action_executions_workflow_run_id_foreign` (`workflow_run_id`),
  KEY `bot_action_executions_bot_action_created` (`bot_id`,`action_key`,`created_at`),
  KEY `bot_action_executions_status_index` (`status`),
  CONSTRAINT `bot_action_executions_bot_conversation_id_foreign` FOREIGN KEY (`bot_conversation_id`) REFERENCES `bot_conversations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_action_executions_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_action_executions_workflow_run_id_foreign` FOREIGN KEY (`workflow_run_id`) REFERENCES `workflow_runs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_compound_item_executions`;
CREATE TABLE `bot_compound_item_executions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `bot_conversation_id` bigint unsigned DEFAULT NULL,
  `bot_compound_request_id` bigint unsigned DEFAULT NULL,
  `capability` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `side_effect` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'write',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'running',
  `result` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bot_compound_item_request_capability_unique` (`bot_compound_request_id`,`capability`,`item_key`),
  KEY `bot_compound_item_executions_bot_conversation_id_foreign` (`bot_conversation_id`),
  KEY `bot_compound_item_bot_capability_created` (`bot_id`,`capability`,`created_at`),
  KEY `bot_compound_item_executions_status_index` (`status`),
  CONSTRAINT `bot_compound_item_executions_bot_compound_request_id_foreign` FOREIGN KEY (`bot_compound_request_id`) REFERENCES `bot_compound_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_compound_item_executions_bot_conversation_id_foreign` FOREIGN KEY (`bot_conversation_id`) REFERENCES `bot_conversations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_compound_item_executions_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_compound_requests`;
CREATE TABLE `bot_compound_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `bot_conversation_id` bigint unsigned NOT NULL,
  `trigger_message_id` bigint unsigned DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `plan` json NOT NULL,
  `result` json DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `agent_graph_run_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_graph_thread_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_graph_interrupt_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_graph_checkpoint_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pending_conversation_guard` bigint unsigned GENERATED ALWAYS AS ((case when ((`status` = _utf8mb4'pending') and (`bot_conversation_id` is not null)) then `bot_conversation_id` else NULL end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bot_compound_mysql_one_pending_unique` (`pending_conversation_guard`),
  KEY `bot_compound_requests_trigger_message_id_foreign` (`trigger_message_id`),
  KEY `bot_compound_conversation_status_expiry_index` (`bot_conversation_id`,`status`,`expires_at`),
  KEY `bot_compound_bot_status_created_index` (`bot_id`,`status`,`created_at`),
  KEY `bot_compound_requests_status_index` (`status`),
  KEY `bot_compound_requests_expires_at_index` (`expires_at`),
  KEY `bot_compound_requests_agent_graph_run_id_index` (`agent_graph_run_id`),
  KEY `bot_compound_requests_agent_graph_thread_id_index` (`agent_graph_thread_id`),
  KEY `bot_compound_requests_agent_graph_interrupt_id_index` (`agent_graph_interrupt_id`),
  CONSTRAINT `bot_compound_requests_bot_conversation_id_foreign` FOREIGN KEY (`bot_conversation_id`) REFERENCES `bot_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_compound_requests_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_compound_requests_trigger_message_id_foreign` FOREIGN KEY (`trigger_message_id`) REFERENCES `bot_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_conversation_task_frames`;
CREATE TABLE `bot_conversation_task_frames` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `bot_conversation_id` bigint unsigned NOT NULL,
  `source_type` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_workflow_id` bigint unsigned DEFAULT NULL,
  `capability_key` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lane` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intent` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `side_effect` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'read',
  `primary_slot` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frame` json NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bot_task_frames_conversation_source_unique` (`bot_conversation_id`,`source_type`,`source_id`),
  KEY `bot_conversation_task_frames_bot_id_foreign` (`bot_id`),
  KEY `bot_conversation_task_frames_agent_workflow_id_foreign` (`agent_workflow_id`),
  KEY `bot_task_frames_conversation_updated_index` (`bot_conversation_id`,`updated_at`),
  KEY `bot_task_frames_conversation_status_index` (`bot_conversation_id`,`status`,`updated_at`),
  KEY `bot_task_frames_conversation_capability_index` (`bot_conversation_id`,`capability_key`,`updated_at`),
  KEY `bot_task_frames_source_index` (`source_type`,`source_id`),
  KEY `bot_conversation_task_frames_capability_key_index` (`capability_key`),
  KEY `bot_conversation_task_frames_lane_index` (`lane`),
  KEY `bot_conversation_task_frames_intent_index` (`intent`),
  KEY `bot_conversation_task_frames_status_index` (`status`),
  KEY `bot_conversation_task_frames_side_effect_index` (`side_effect`),
  KEY `bot_conversation_task_frames_expires_at_index` (`expires_at`),
  CONSTRAINT `bot_conversation_task_frames_agent_workflow_id_foreign` FOREIGN KEY (`agent_workflow_id`) REFERENCES `agent_workflows` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_conversation_task_frames_bot_conversation_id_foreign` FOREIGN KEY (`bot_conversation_id`) REFERENCES `bot_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_conversation_task_frames_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_conversations`;
CREATE TABLE `bot_conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `context_area` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bot_access_token_id` bigint unsigned DEFAULT NULL,
  `owner_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel_connection_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bot_conversations_bot_session_area_unique` (`bot_id`,`session_id`,`context_area`),
  KEY `bot_conversations_bot_id_session_id_index` (`bot_id`,`session_id`),
  KEY `bot_conversations_session_id_index` (`session_id`),
  KEY `bot_conversations_context_area_index` (`context_area`),
  KEY `bot_conversations_channel_connection_id_foreign` (`channel_connection_id`),
  KEY `bot_conversations_token_area_index` (`bot_access_token_id`,`context_area`),
  KEY `bot_conversations_owner_index` (`owner_type`,`owner_id`),
  KEY `bot_conversations_channel_area_index` (`channel`,`context_area`),
  CONSTRAINT `bot_conversations_bot_access_token_id_foreign` FOREIGN KEY (`bot_access_token_id`) REFERENCES `bot_access_tokens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_conversations_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_conversations_channel_connection_id_foreign` FOREIGN KEY (`channel_connection_id`) REFERENCES `channel_connections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_handoff_requests`;
CREATE TABLE `bot_handoff_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `bot_conversation_id` bigint unsigned DEFAULT NULL,
  `workflow_run_id` bigint unsigned DEFAULT NULL,
  `agent_workflow_id` bigint unsigned DEFAULT NULL,
  `trigger_message_id` bigint unsigned DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `priority` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `source` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'operator',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `summary` text COLLATE utf8mb4_unicode_ci,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_to_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_to_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_to_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_handoff_requests_workflow_run_id_foreign` (`workflow_run_id`),
  KEY `bot_handoff_requests_trigger_message_id_foreign` (`trigger_message_id`),
  KEY `bot_handoff_requests_bot_status_priority_index` (`bot_id`,`status`,`priority`),
  KEY `bot_handoff_requests_conversation_created_index` (`bot_conversation_id`,`created_at`),
  KEY `bot_handoff_requests_workflow_status_index` (`agent_workflow_id`,`status`),
  CONSTRAINT `bot_handoff_requests_agent_workflow_id_foreign` FOREIGN KEY (`agent_workflow_id`) REFERENCES `agent_workflows` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_handoff_requests_bot_conversation_id_foreign` FOREIGN KEY (`bot_conversation_id`) REFERENCES `bot_conversations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_handoff_requests_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_handoff_requests_trigger_message_id_foreign` FOREIGN KEY (`trigger_message_id`) REFERENCES `bot_messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_handoff_requests_workflow_run_id_foreign` FOREIGN KEY (`workflow_run_id`) REFERENCES `workflow_runs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_knowledge_chunks`;
CREATE TABLE `bot_knowledge_chunks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `knowledge_document_id` bigint unsigned NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `chunk_index` int NOT NULL,
  `token_count` int DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `embedding` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `bot_knowledge_chunks_knowledge_document_id_chunk_index_index` (`knowledge_document_id`,`chunk_index`),
  CONSTRAINT `bot_knowledge_chunks_knowledge_document_id_foreign` FOREIGN KEY (`knowledge_document_id`) REFERENCES `bot_knowledge_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_knowledge_documents`;
CREATE TABLE `bot_knowledge_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `knowledge_source_id` bigint unsigned NOT NULL,
  `content_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_knowledge_documents_knowledge_source_id_foreign` (`knowledge_source_id`),
  KEY `bot_knowledge_documents_content_hash_index` (`content_hash`),
  CONSTRAINT `bot_knowledge_documents_knowledge_source_id_foreign` FOREIGN KEY (`knowledge_source_id`) REFERENCES `bot_knowledge_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_knowledge_sources`;
CREATE TABLE `bot_knowledge_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_knowledge_sources_bot_id_foreign` (`bot_id`),
  CONSTRAINT `bot_knowledge_sources_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_messages`;
CREATE TABLE `bot_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_conversation_id` bigint unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sources` json DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_messages_bot_conversation_id_foreign` (`bot_conversation_id`),
  CONSTRAINT `bot_messages_bot_conversation_id_foreign` FOREIGN KEY (`bot_conversation_id`) REFERENCES `bot_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_pending_interactions`;
CREATE TABLE `bot_pending_interactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `bot_conversation_id` bigint unsigned NOT NULL,
  `workflow_run_id` bigint unsigned DEFAULT NULL,
  `bot_message_id` bigint unsigned DEFAULT NULL,
  `source_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `kind` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agent_graph_run_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_graph_thread_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_graph_interrupt_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agent_graph_checkpoint_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `node_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interrupt_payload_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expects` json DEFAULT NULL,
  `context` json DEFAULT NULL,
  `resolution` json DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `draft_value` json DEFAULT NULL,
  `draft_current_step` int unsigned DEFAULT NULL,
  `draft_schema_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `draft_saved_at` timestamp NULL DEFAULT NULL,
  `pending_conversation_guard` bigint unsigned GENERATED ALWAYS AS ((case when ((`status` = _utf8mb4'pending') and (`bot_conversation_id` is not null)) then `bot_conversation_id` else NULL end)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bot_pending_conversation_interrupt_hash_unique` (`bot_conversation_id`,`agent_graph_interrupt_id`,`interrupt_payload_hash`),
  UNIQUE KEY `bot_pending_mysql_one_pending_unique` (`pending_conversation_guard`),
  KEY `bot_pending_interactions_bot_id_foreign` (`bot_id`),
  KEY `bot_pending_interactions_bot_message_id_foreign` (`bot_message_id`),
  KEY `bot_pending_conversation_status_created_index` (`bot_conversation_id`,`status`,`created_at`),
  KEY `bot_pending_run_status_index` (`workflow_run_id`,`status`),
  KEY `bot_pending_interactions_status_index` (`status`),
  KEY `bot_pending_interactions_agent_graph_run_id_index` (`agent_graph_run_id`),
  KEY `bot_pending_interactions_agent_graph_thread_id_index` (`agent_graph_thread_id`),
  KEY `bot_pending_interactions_expires_at_index` (`expires_at`),
  KEY `bot_pending_interactions_draft_schema_hash_index` (`draft_schema_hash`),
  KEY `bot_pending_interactions_draft_saved_at_index` (`draft_saved_at`),
  CONSTRAINT `bot_pending_interactions_bot_conversation_id_foreign` FOREIGN KEY (`bot_conversation_id`) REFERENCES `bot_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_pending_interactions_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_pending_interactions_bot_message_id_foreign` FOREIGN KEY (`bot_message_id`) REFERENCES `bot_messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_pending_interactions_workflow_run_id_foreign` FOREIGN KEY (`workflow_run_id`) REFERENCES `workflow_runs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_quality_runs`;
CREATE TABLE `bot_quality_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_quality_scenario_id` bigint unsigned NOT NULL,
  `bot_id` bigint unsigned NOT NULL,
  `agent_workflow_id` bigint unsigned DEFAULT NULL,
  `workflow_run_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `target` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direct_bot',
  `workflow_draft_fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scenario_fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` tinyint unsigned DEFAULT NULL,
  `checks` json DEFAULT NULL,
  `response_excerpt` text COLLATE utf8mb4_unicode_ci,
  `failure_summary` text COLLATE utf8mb4_unicode_ci,
  `latency_ms` int unsigned DEFAULT NULL,
  `prompt_tokens` int unsigned NOT NULL DEFAULT '0',
  `completion_tokens` int unsigned NOT NULL DEFAULT '0',
  `cost_cents` int unsigned DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_quality_runs_workflow_run_id_foreign` (`workflow_run_id`),
  KEY `bot_quality_runs_bot_id_status_index` (`bot_id`,`status`),
  KEY `bot_quality_runs_agent_workflow_id_status_index` (`agent_workflow_id`,`status`),
  KEY `bot_quality_runs_workflow_draft_fingerprint_index` (`agent_workflow_id`,`workflow_draft_fingerprint`),
  KEY `bot_quality_runs_scenario_fingerprint_index` (`bot_quality_scenario_id`,`scenario_fingerprint`),
  KEY `bot_quality_runs_bot_quality_scenario_id_created_at_index` (`bot_quality_scenario_id`,`created_at`),
  CONSTRAINT `bot_quality_runs_agent_workflow_id_foreign` FOREIGN KEY (`agent_workflow_id`) REFERENCES `agent_workflows` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_quality_runs_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_quality_runs_bot_quality_scenario_id_foreign` FOREIGN KEY (`bot_quality_scenario_id`) REFERENCES `bot_quality_scenarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_quality_runs_workflow_run_id_foreign` FOREIGN KEY (`workflow_run_id`) REFERENCES `workflow_runs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_quality_scenarios`;
CREATE TABLE `bot_quality_scenarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `agent_workflow_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `user_message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `context_messages` json DEFAULT NULL,
  `expectations` json DEFAULT NULL,
  `is_blocking` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `source_bot_message_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bot_quality_scenarios_source_message_unique` (`source_bot_message_id`),
  KEY `bot_quality_scenarios_bot_id_is_active_index` (`bot_id`,`is_active`),
  KEY `bot_quality_scenarios_agent_workflow_id_is_active_index` (`agent_workflow_id`,`is_active`),
  CONSTRAINT `bot_quality_scenarios_agent_workflow_id_foreign` FOREIGN KEY (`agent_workflow_id`) REFERENCES `agent_workflows` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_quality_scenarios_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_quality_scenarios_source_bot_message_id_foreign` FOREIGN KEY (`source_bot_message_id`) REFERENCES `bot_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_submission_audits`;
CREATE TABLE `bot_submission_audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_submission_id` bigint unsigned NOT NULL,
  `bot_id` bigint unsigned NOT NULL,
  `bot_conversation_id` bigint unsigned DEFAULT NULL,
  `workflow_run_id` bigint unsigned DEFAULT NULL,
  `agent_workflow_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_submission_audits_bot_conversation_id_foreign` (`bot_conversation_id`),
  KEY `bot_submission_audits_agent_workflow_id_foreign` (`agent_workflow_id`),
  KEY `bot_submission_audits_submission_created_index` (`bot_submission_id`,`created_at`),
  KEY `bot_submission_audits_bot_event_index` (`bot_id`,`event_type`),
  KEY `bot_submission_audits_run_created_index` (`workflow_run_id`,`created_at`),
  CONSTRAINT `bot_submission_audits_agent_workflow_id_foreign` FOREIGN KEY (`agent_workflow_id`) REFERENCES `agent_workflows` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_submission_audits_bot_conversation_id_foreign` FOREIGN KEY (`bot_conversation_id`) REFERENCES `bot_conversations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_submission_audits_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_submission_audits_bot_submission_id_foreign` FOREIGN KEY (`bot_submission_id`) REFERENCES `bot_submissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_submission_audits_workflow_run_id_foreign` FOREIGN KEY (`workflow_run_id`) REFERENCES `workflow_runs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_submissions`;
CREATE TABLE `bot_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `bot_conversation_id` bigint unsigned DEFAULT NULL,
  `workflow_run_id` bigint unsigned DEFAULT NULL,
  `agent_workflow_id` bigint unsigned DEFAULT NULL,
  `schema_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `schema_version` smallint unsigned NOT NULL DEFAULT '1',
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `dedupe_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json NOT NULL,
  `meta` json DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bot_submissions_bot_schema_dedupe_unique` (`bot_id`,`schema_key`,`dedupe_key`),
  KEY `bot_submissions_workflow_run_id_foreign` (`workflow_run_id`),
  KEY `bot_submissions_agent_workflow_id_foreign` (`agent_workflow_id`),
  KEY `bot_submissions_bot_schema_status_index` (`bot_id`,`schema_key`,`status`),
  KEY `bot_submissions_conversation_created_index` (`bot_conversation_id`,`created_at`),
  CONSTRAINT `bot_submissions_agent_workflow_id_foreign` FOREIGN KEY (`agent_workflow_id`) REFERENCES `agent_workflows` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_submissions_bot_conversation_id_foreign` FOREIGN KEY (`bot_conversation_id`) REFERENCES `bot_conversations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_submissions_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_submissions_workflow_run_id_foreign` FOREIGN KEY (`workflow_run_id`) REFERENCES `workflow_runs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_usage_budget_reservations`;
CREATE TABLE `bot_usage_budget_reservations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `bot_access_token_id` bigint unsigned DEFAULT NULL,
  `bot_usage_event_id` bigint unsigned DEFAULT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reserved',
  `reserved_tokens` bigint unsigned NOT NULL DEFAULT '0',
  `reserved_cost_cents` decimal(12,4) DEFAULT NULL,
  `actual_tokens` bigint unsigned DEFAULT NULL,
  `actual_cost_cents` decimal(12,4) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_usage_budget_reservations_bot_usage_event_id_foreign` (`bot_usage_event_id`),
  KEY `budget_reservations_bot_status_expiry` (`bot_id`,`status`,`expires_at`),
  KEY `budget_reservations_token_status_expiry` (`bot_access_token_id`,`status`,`expires_at`),
  CONSTRAINT `bot_usage_budget_reservations_bot_access_token_id_foreign` FOREIGN KEY (`bot_access_token_id`) REFERENCES `bot_access_tokens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_usage_budget_reservations_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_usage_budget_reservations_bot_usage_event_id_foreign` FOREIGN KEY (`bot_usage_event_id`) REFERENCES `bot_usage_events` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bot_usage_events`;
CREATE TABLE `bot_usage_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` bigint unsigned NOT NULL,
  `bot_access_token_id` bigint unsigned DEFAULT NULL,
  `bot_conversation_id` bigint unsigned DEFAULT NULL,
  `workflow_run_id` bigint unsigned DEFAULT NULL,
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'chat',
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prompt_tokens` int unsigned NOT NULL DEFAULT '0',
  `completion_tokens` int unsigned NOT NULL DEFAULT '0',
  `reasoning_tokens` int unsigned NOT NULL DEFAULT '0',
  `total_tokens` int unsigned NOT NULL DEFAULT '0',
  `estimated_cost_cents` decimal(12,4) DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_usage_events_bot_conversation_id_foreign` (`bot_conversation_id`),
  KEY `bot_usage_events_workflow_run_id_foreign` (`workflow_run_id`),
  KEY `bot_usage_events_bot_id_occurred_at_index` (`bot_id`,`occurred_at`),
  KEY `bot_usage_events_token_occurred_index` (`bot_access_token_id`,`occurred_at`),
  CONSTRAINT `bot_usage_events_bot_access_token_id_foreign` FOREIGN KEY (`bot_access_token_id`) REFERENCES `bot_access_tokens` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_usage_events_bot_conversation_id_foreign` FOREIGN KEY (`bot_conversation_id`) REFERENCES `bot_conversations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bot_usage_events_bot_id_foreign` FOREIGN KEY (`bot_id`) REFERENCES `agentic_bots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bot_usage_events_workflow_run_id_foreign` FOREIGN KEY (`workflow_run_id`) REFERENCES `workflow_runs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;