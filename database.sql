-- ================================================
-- 晴玖AI创作系统 - 数据库初始化脚本
-- ================================================

-- ----------------------------
-- 用户表
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '用户ID',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `email` VARCHAR(100) NOT NULL COMMENT '邮箱',
  `password` VARCHAR(255) NOT NULL COMMENT '密码(哈希)',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像',
  `points` INT DEFAULT 1 COMMENT '积分',
  `balance` DECIMAL(10,2) DEFAULT 0.00 COMMENT '余额',
  `member_level` INT DEFAULT 1 COMMENT '会员等级',
  `status` INT DEFAULT 1 COMMENT '状态(1=正常,0=禁用)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- ----------------------------
-- 作品表
-- ----------------------------
DROP TABLE IF EXISTS `works`;
CREATE TABLE `works` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '作品ID',
  `user_id` INT NOT NULL COMMENT '用户ID',
  `prompt` TEXT NOT NULL COMMENT '描述词',
  `image_url` VARCHAR(500) NOT NULL COMMENT '图片URL',
  `size` VARCHAR(20) DEFAULT '1024x1024' COMMENT '图片尺寸',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='作品表';

-- ----------------------------
-- 商城商品表
-- ----------------------------
DROP TABLE IF EXISTS `shop_items`;
CREATE TABLE `shop_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '商品ID',
  `name` VARCHAR(100) NOT NULL COMMENT '商品名称',
  `description` VARCHAR(500) DEFAULT '' COMMENT '商品描述',
  `price_points` INT NOT NULL COMMENT '价格(积分)',
  `value` INT NOT NULL COMMENT '兑换数量',
  `stock` INT DEFAULT 999 COMMENT '库存',
  `status` INT DEFAULT 1 COMMENT '状态(1=上架,0=下架)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商城商品表';

-- ----------------------------
-- 商城订单表
-- ----------------------------
DROP TABLE IF EXISTS `shop_orders`;
CREATE TABLE `shop_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '订单ID',
  `user_id` INT NOT NULL COMMENT '用户ID',
  `item_id` INT NOT NULL COMMENT '商品ID',
  `item_name` VARCHAR(100) NOT NULL COMMENT '商品名称',
  `price_points` INT NOT NULL COMMENT '消耗积分',
  `value` INT NOT NULL COMMENT '获得数量',
  `status` INT DEFAULT 1 COMMENT '状态(1=已完成,0=失败)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商城订单表';

-- ----------------------------
-- 充值订单表
-- ----------------------------
DROP TABLE IF EXISTS `recharge_orders`;
CREATE TABLE `recharge_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '订单ID',
  `order_no` VARCHAR(50) NOT NULL COMMENT '订单编号',
  `user_id` INT NOT NULL COMMENT '用户ID',
  `amount` DECIMAL(10,2) NOT NULL COMMENT '充值金额',
  `points` INT NOT NULL COMMENT '获得积分',
  `status` INT DEFAULT 0 COMMENT '状态(0=待支付,1=已完成,2=失败)',
  `pay_type` VARCHAR(20) DEFAULT 'alipay' COMMENT '支付方式',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='充值订单表';

-- ----------------------------
-- 账户日志表
-- ----------------------------
DROP TABLE IF EXISTS `account_logs`;
CREATE TABLE `account_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '日志ID',
  `user_id` INT NOT NULL COMMENT '用户ID',
  `type` VARCHAR(20) NOT NULL COMMENT '类型(points/balance)',
  `change_type` VARCHAR(20) NOT NULL COMMENT '变动类型(income/expense)',
  `amount` DECIMAL(10,2) NOT NULL COMMENT '变动金额',
  `balance` DECIMAL(10,2) NOT NULL COMMENT '变动后余额',
  `remark` VARCHAR(200) DEFAULT '' COMMENT '备注',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='账户日志表';

-- ----------------------------
-- 工单表
-- ----------------------------
DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '工单ID',
  `user_id` INT NOT NULL COMMENT '用户ID',
  `title` VARCHAR(200) NOT NULL COMMENT '工单标题',
  `content` TEXT NOT NULL COMMENT '工单内容',
  `status` INT DEFAULT 0 COMMENT '状态(0=待处理,1=处理中,2=已完成)',
  `reply` TEXT DEFAULT NULL COMMENT '回复内容',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工单表';

-- ----------------------------
-- API配置表
-- ----------------------------
DROP TABLE IF EXISTS `api_configs`;
CREATE TABLE `api_configs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '配置ID',
  `user_id` INT NOT NULL COMMENT '用户ID',
  `provider` VARCHAR(50) NOT NULL COMMENT '接口提供商',
  `api_key` VARCHAR(255) NOT NULL COMMENT 'API密钥',
  `model` VARCHAR(100) DEFAULT NULL COMMENT '模型名称',
  `endpoint` VARCHAR(500) DEFAULT NULL COMMENT '自定义接口地址',
  `status` INT DEFAULT 0 COMMENT '状态(0=禁用,1=启用)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='API配置表';

-- ----------------------------
-- 系统配置表
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '配置ID',
  `config_key` VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
  `config_value` TEXT COMMENT '配置值',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

-- ----------------------------
-- 卡密表
-- ----------------------------
DROP TABLE IF EXISTS `card_keys`;
CREATE TABLE `card_keys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT '卡密ID',
  `card_key` VARCHAR(100) NOT NULL UNIQUE COMMENT '卡密',
  `card_type` VARCHAR(20) NOT NULL COMMENT '类型(points/member)',
  `value` INT NOT NULL COMMENT '值(积分/天数)',
  `days` INT DEFAULT NULL COMMENT '有效期天数(会员卡密)',
  `used` INT DEFAULT 0 COMMENT '是否使用(0=未使用,1=已使用)',
  `used_by` INT DEFAULT NULL COMMENT '使用者ID',
  `used_at` TIMESTAMP NULL DEFAULT NULL COMMENT '使用时间',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  FOREIGN KEY (`used_by`) REFERENCES `user`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密表';

-- ----------------------------
-- 初始数据
-- ----------------------------

-- 商城商品初始数据
INSERT INTO `shop_items` (`name`, `description`, `price_points`, `value`, `stock`) VALUES
('500次大客户特惠', '单次低至0.1元', 50, 500, 999),
('150次特惠套餐', '单次低至0.2元', 30, 150, 999),
('67次进阶套餐', '单次低至0.3元', 20, 67, 999),
('25次优惠套餐', '单次低至0.4元', 10, 25, 999),
('2次体验卡', '单次默认0.5元', 2, 2, 999);

-- 测试卡密数据
INSERT INTO `card_keys` (`card_key`, `card_type`, `value`, `days`) VALUES
('TEST-POINTS-100-2024', 'points', 100, NULL),
('TEST-POINTS-500-2024', 'points', 500, NULL),
('TEST-MEMBER-7-2024', 'member', 7, 7),
('TEST-MEMBER-30-2024', 'member', 30, 30);

-- ================================================
-- 初始化管理员账号
-- 请执行以下SQL插入管理员账号（密码：123456）
-- ================================================
INSERT INTO `user` (`username`, `email`, `password`, `points`, `balance`, `member_level`, `status`)
VALUES ('admin', '123@qq.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 100, 0.00, 1, 1);

-- ================================================
-- 观赏大厅初始作品数据 - 美女主题
-- ================================================
INSERT INTO `works` (`user_id`, `prompt`, `image_url`, `size`, `created_at`) VALUES
(1, 'Beautiful young woman with long flowing hair, wearing elegant dress, standing in a flower garden, soft natural light, professional portrait photography', 'https://neeko-copilot.bytedance.net/api/text_to_image?prompt=Beautiful%20young%20woman%20with%20long%20flowing%20hair%20wearing%20elegant%20dress%20standing%20in%20flower%20garden%20soft%20natural%20light%20professional%20portrait&image_size=square', '1024x1024', '2024-05-25 10:30:00'),
(1, 'Gorgeous asian beauty with black hair, wearing traditional hanfu dress, graceful pose, ancient chinese garden background', 'https://neeko-copilot.bytedance.net/api/text_to_image?prompt=Gorgeous%20asian%20beauty%20with%20black%20hair%20wearing%20traditional%20hanfu%20dress%20graceful%20pose%20ancient%20chinese%20garden&image_size=square', '1024x1024', '2024-05-25 11:45:00'),
(1, 'Elegant fashion model with perfect makeup, wearing red evening gown, studio lighting, high fashion photography', 'https://neeko-copilot.bytedance.net/api/text_to_image?prompt=Elegant%20fashion%20model%20with%20perfect%20makeup%20wearing%20red%20evening%20gown%20studio%20lighting%20high%20fashion%20photography&image_size=square', '1024x1024', '2024-05-25 14:20:00'),
(1, 'Cute girl with curly hair, casual street style outfit, urban background, candid photography style', 'https://neeko-copilot.bytedance.net/api/text_to_image?prompt=Cute%20girl%20with%20curly%20hair%20casual%20street%20style%20outfit%20urban%20background%20candid%20photography&image_size=square', '1024x1024', '2024-05-25 16:00:00'),
(1, 'Beautiful woman with blonde hair, beach sunset backdrop, wearing summer dress, golden hour lighting', 'https://neeko-copilot.bytedance.net/api/text_to_image?prompt=Beautiful%20woman%20with%20blonde%20hair%20beach%20sunset%20backdrop%20wearing%20summer%20dress%20golden%20hour%20lighting&image_size=square', '1024x1024', '2024-05-26 09:15:00'),
(1, 'Stunning woman with elegant updo hairstyle, wearing pearl necklace, formal evening wear, luxury interior setting', 'https://neeko-copilot.bytedance.net/api/text_to_image?prompt=Stunning%20woman%20with%20elegant%20updo%20hairstyle%20wearing%20pearl%20necklace%20formal%20evening%20wear%20luxury%20interior&image_size=square', '1024x1024', '2024-05-26 10:30:00'),
(1, 'Young beautiful woman with glasses, intellectual style, modern cafe background, natural smile', 'https://neeko-copilot.bytedance.net/api/text_to_image?prompt=Young%20beautiful%20woman%20with%20glasses%20intellectual%20style%20modern%20cafe%20background%20natural%20smile&image_size=square', '1024x1024', '2024-05-26 15:45:00'),
(1, 'Fashionable woman with bob haircut, wearing leather jacket, edgy style, city street night scene with neon lights', 'https://neeko-copilot.bytedance.net/api/text_to_image?prompt=Fashionable%20woman%20with%20bob%20haircut%20wearing%20leather%20jacket%20edgy%20style%20city%20street%20night%20scene%20neon%20lights&image_size=square', '1024x1024', '2024-05-27 08:00:00'),
(1, 'Lovely woman with braided hair, bohemian style dress, sitting in a field of lavender flowers, soft sunlight', 'https://neeko-copilot.bytedance.net/api/text_to_image?prompt=Lovely%20woman%20with%20braided%20hair%20bohemian%20style%20dress%20sitting%20in%20field%20of%20lavender%20flowers%20soft%20sunlight&image_size=square', '1024x1024', '2024-05-27 11:20:00'),
(1, 'Graceful ballerina in white tutu, dance studio background, elegant pose, soft lighting, artistic photography', 'https://neeko-copilot.bytedance.net/api/text_to_image?prompt=Graceful%20ballerina%20in%20white%20tutu%20dance%20studio%20background%20elegant%20pose%20soft%20lighting%20artistic%20photography&image_size=square', '1024x1024', '2024-05-27 14:30:00');