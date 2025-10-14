/*
 Navicat Premium Data Transfer

 Source Server         : Valdi
 Source Server Type    : MySQL
 Source Server Version : 80017
 Source Host           : localhost:3306
 Source Schema         : pidecredito

 Target Server Type    : MySQL
 Target Server Version : 80017
 File Encoding         : 65001

 Date: 14/10/2025 15:00:28
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for pipelineinbox
-- ----------------------------
DROP TABLE IF EXISTS `pipelineinbox`;
CREATE TABLE `pipelineinbox`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pipeline_id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `fromnumber` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `source_phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `active` int(5) NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uniq_pipe_chat`(`pipeline_id`, `chat_id`) USING BTREE,
  INDEX `pipeline_id`(`pipeline_id`) USING BTREE,
  INDEX `chat_id`(`chat_id`) USING BTREE,
  CONSTRAINT `pipelineinbox_ibfk_1` FOREIGN KEY (`pipeline_id`) REFERENCES `pipelines` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
