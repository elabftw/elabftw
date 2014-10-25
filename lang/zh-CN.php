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
define("YES", "是");
define("NO", "否");
define("CONFIG_UPDATE_OK", "配置已更新成功。");
define("ERROR_BUG", "发生意料之外的问题！如果你认为这是一个 BUG，请<a href='https://github.com/NicolasCARPi/elabftw/issues/'>在 GitHub 上提交一个错误报告</a>。");
define("INVALID_ID", "ID 参数无效！");
define("INVALID_USERID", "用户名无效。");
define("INVALID_TYPE", "类型参数无效！");
define("INVALID_FORMKEY", "表格键无效。请重试。");
define("INVALID_EMAIL", "Email 地址无效。");
define("INVALID_PASSWORD", "密码错误！");
define("INVALID_USER", "无用户使用此 Email 或用户不再您的团队中");
define("PASSWORDS_DONT_MATCH", "密码不匹配！");
define("PASSWORD_TOO_SHORT", "密码至少包含 8 个字符。");
define("NEED_TITLE", "您需要输入一个标题！");
define("NEED_PASSWORD", "您需要输入一个密码！");
define("FIELD_MISSING", "缺失必填字段！");
define("NO_ACCESS_DIE", "此会话已超出允许的范围。");

define("USERNAME", "用户名");
define("PASSWORD", "密码");
define("FIRSTNAME", "名");
define("LASTNAME", "姓");
define("EMAIL", "Email");
define("TEAM", "团队");

define("DATE", "日期");
define("TITLE", "标题");
define("INFOS", "信息");
define("VISIBILITY", "可见性");
define("STATUS", "状态");

define("EDIT", "编辑");
define("SAVE", "保存");
define("SAVED", "已保存！");
define("CANCEL", "取消");
define("SAVING", "正在保存");
define("SAVE_AND_BACK", "保存并返回");
define("UPDATED", "已更新！");

define("ACTION", "动作");
define("SHORTCUT", "快捷键");
define("CREATE", "创建");
define("SUBMIT", "提交");
define("TODO", "任务列表");

define("NAME", "姓名");
define("PHONE", "电话");
define("MOBILE", "移动电话");
define("WEBSITE", "网站");
define("SKYPE", "QQ");

// EMAILS
define("EMAIL_NEW_USER_SUBJECT", "[eLabFTW] 新用户已注册");
define("EMAIL_NEW_USER_BODY_1", "您好,
    您在 eLabFTW 中的账号已激活。您现在可以登录：");
define("EMAIL_SEND_ERROR", "发送 Email 时发生错误！错误已被记录。");
define("EMAIL_SUCCESS", "Email 已发送。请检查您的收件箱。");
define("EMAIL_FOOTER", "

~~~
Email 由 eLabFTW 发送
http://www.elabftw.net
Free open-source Lab Manager");

// ADMIN.PHP
define("ADMIN_TITLE", "管理面板");

define("ADMIN_VALIDATION_QUEUE", "有用户正等待验证他们的账号：");
define("ADMIN_VALIDATION_QUEUE_SUBMIT", "验证用户");

define("ADMIN_MENU_TEAM", "团队");
define("ADMIN_MENU_USERS", "用户");
define("ADMIN_MENU_ITEMSTYPES", "项目类型");
define("ADMIN_MENU_EXPTPL", "实验记录模版");
define("ADMIN_MENU_CSV", "导入 CSV");

define("ADMIN_TEAM_H3", "配置您的团队");
define("ADMIN_TEAM_DELETABLE_XP", "允许用户删除实验：");
define("ADMIN_TEAM_LINK_NAME", "顶部菜单链接名称：");
define("ADMIN_TEAM_LINK_HREF", "链接指向的地址：");
define("ADMIN_TEAM_STAMPLOGIN", "登录到外部时间戳服务：");
define("ADMIN_TEAM_STAMPLOGIN_HELP", "在此输入您的 Universign.com 账号关联的 Email 地址。");
define("ADMIN_TEAM_STAMPPASS", "外部时间戳服务密码：");
define("ADMIN_TEAM_STAMPPASS_HELP", "您的 Universign 密码");

define("ADMIN_USERS_H3", "编辑用户");
define("ADMIN_USERS_VALIDATED", "账号以激活？");
define("ADMIN_USERS_GROUP", "用户组：");
define("ADMIN_USERS_RESET_PASSWORD", "重置用户密码：");
define("ADMIN_USERS_REPEAT_PASSWORD", "重复新密码：");
define("ADMIN_USERS_BUTTON", "编辑此用户");

define("ADMIN_DELETE_USER_H3", "危险区域");
define("ADMIN_DELETE_USER_H4", "删除账号");
define("ADMIN_DELETE_USER_HELP", "输入一个用户的 Email 地址来永久删除此用户及其所有实验 / 文件：");
define("ADMIN_DELETE_USER_CONFPASS", "输入您的密码：");
define("ADMIN_DELETE_USER_BUTTON", "删除此用户！");

define("ADMIN_STATUS_ADD_H3", "添加一个新状态");
define("ADMIN_STATUS_ADD_NEW", "新状态名称：");
define("ADMIN_STATUS_ADD_BUTTON", "添加新状态");
define("ADMIN_STATUS_EDIT_H3", "编辑已有的状态");
define("ADMIN_STATUS_EDIT_ALERT", "删除此状态前请将所有实验记录从此状态中移除。");
define("ADMIN_STATUS_EDIT_DEFAULT", "设为默认");

define("ADMIN_ITEMS_TYPES_H3", "数据库项目类型");
define("ADMIN_ITEMS_TYPES_ALERT", "删除此类型前请将所有数据库项目从此类型中移除。");
define("ADMIN_ITEMS_TYPES_EDIT_NAME", "编辑名称：");
define("ADMIN_ITEMS_TYPES_ADD", "添加新项目类型：");
define("ADMIN_ITEMS_TYPES_ADD_BUTTON", "添加新项目类型");

define("ADMIN_EXPERIMENT_TEMPLATE_H3", "公共实验记录模版");
define("ADMIN_EXPERIMENT_TEMPLATE_HELP", "这是创建新实验记录的默认模版。");

define("ADMIN_IMPORT_CSV_H3", "导入 CSV 文件");
define("ADMIN_IMPORT_CSV_HELP", "此页面运行您导入一个 .csv (Excel 电子表格) 文件到数据库中。<br>首先您需要在 Excel 或 Libreoffice 中打开 (.xls/.xlsx) 文件并将其保存为 .csv。<br>为了能正确导入，第一列应为标题。其余列将被导入到内容中。在导入大文件之前，您可以先尝试导入 3 行数据来检查是否一切工作正常。");
define("ADMIN_IMPORT_CSV_HELP_STRONG", "在导入数千个项目之前务必备份您的数据库！");
define("ADMIN_IMPORT_CSV_STEP_1", "1. 选择要导入的项目类型：");
define("ADMIN_IMPORT_CSV_STEP_2", "2. 选择要导入的 CSV 文件：");
define("ADMIN_IMPORT_CSV_BUTTON", "导入 CSV");
define("ADMIN_IMPORT_CSV_MSG", "个项目被成功导入。");

// ADMIN-EXEC.PHP
define("ADMIN_USER_VALIDATED", "Validated user with ID ：");
define("ADMIN_TEAM_ADDED", "Team added successfully.");
define("SYSADMIN_GRANT_SYSADMIN", "Only a sysadmin can put someone sysadmin.");
define("USER_DELETED", "Everything was purged successfully.");

// CHANGE-PASS.PHP
define("CHANGE_PASS_TITLE", "重置密码");
define("CHANGE_PASS_PASSWORD", "新密码");
define("CHANGE_PASS_REPEAT_PASSWORD", "请再输入一次");
define("CHANGE_PASS_HELP", "最少 8 个字符");
define("CHANGE_PASS_COMPLEXITY", "强度");
define("CHANGE_PASS_BUTTON", "保存新密码");
// this is in JS code, so probably not a good idea to put ' or "
define("CHANGE_PASS_WEAK", "弱");
define("CHANGE_PASS_AVERAGE", "中等");
define("CHANGE_PASS_GOOD", "较好");
define("CHANGE_PASS_STRONG", "强");
define("CHANGE_PASS_NO_WAY", "想把这个设为密码？没门！");

// CHECK_FOR_UPDATES.PHP
define("CHK_UPDATE_GIT", "安装 GIT 来检查更新。");
define("CHK_UPDATE_CURL", "您需要为 PHP 安装 cURL 扩展。");
define("CHK_UPDATE_UNKNOWN", "未知分支！");
define("CHK_UPDATE_GITHUB", "无法连接到 github.com 来检查更新。");
define("CHK_UPDATE_NEW", "有更新可用！");
define("CHK_UPDATE_MASTER", "祝贺您！您正在运行最新的稳定版 eLabFTW :)");
define("CHK_UPDATE_NEXT", "祝贺您！您正在运行最新的开发版 eLabFTW :)");

// CREATE_ITEM.PHP
define("CREATE_ITEM_WRONG_TYPE", "错误的项目类型！");
define("CREATE_ITEM_UNTITLED", "无标题");
define("CREATE_ITEM_SUCCESS", "已成功创建新项目。");

// DATABASE.PHP
define("DATABASE_TITLE", "数据库");

// DELETE_FILE.PHP
define("DELETE_FILE_FILE", "文件");
define("DELETE_FILE_DELETED", "已成功删除");

// DELETE.PHP
define("DELETE_NO_RIGHTS", "您无权删除此实验记录。");
define("DELETE_EXP_SUCCESS", "已成功删除实验记录。");
define("DELETE_TPL_SUCCESS", "已成功删除模版。");
define("DELETE_ITEM_SUCCESS", "已成功删除项目。");
define("DELETE_ITEM_TYPE_SUCCESS", "已成功删除项目类型。");
define("DELETE_STATUS_SUCCESS", "已成功删除状态。");

// DUPLICATE_ITEM.PHP
define("DUPLICATE_EXP_SUCCESS", "已成功复制实验记录。");
define("DUPLICATE_ITEM_SUCCESS", "已成功复制数据库记录。");

// EXPERIMENTS.PHP
define("EXPERIMENTS_TITLE", "实验记录");

// LOCK.PHP
define("LOCK_NO_RIGHTS", "您无权锁定 / 解锁。");
define("LOCK_LOCKED_BY", "此实验记录已被下列用户锁定");
define("LOCK_NO_EDIT", "您不能解锁或编辑已添加时间戳的实验记录。");

// LOGIN-EXEC.PHP
define("LOGIN_FAILED", "登录失败。您输入的密码错误或账号未激活。");

// LOGIN.PHP
define("LOGIN", "登录");
define("LOGIN_TOO_MUCH_FAILED", "因为多次登录失败，您暂时不能登录。");
define("LOGIN_ATTEMPT_NB", "在");
define("LOGIN_MINUTES", "分钟内您还可以尝试登录的次数：");
// in JS code
define("LOGIN_ENABLE_COOKIES", "请在您的浏览器中启用 Cookie 来继续。");
define("LOGIN_COOKIES_NOTE", "注意：您需要启用 Cookie 来登录。");
define("LOGIN_H2", "登录您的账号");
define("LOGIN_FOOTER", "没有账号？<a href='register.php'>马上注册</a>！<br>忘记密码？<a href='#' class='trigger'>重置密码</a>！");
define("LOGIN_FOOTER_PLACEHOLDER", "输入您的 Email 地址");
define("LOGIN_FOOTER_BUTTON", "发送新密码");

// MAKE_CSV.PHP
define("CSV_TITLE", "导出到电子表格");
define("CSV_READY", "您的 CSV 文件已准备好：");

// MAKE_ZIP.PHP
define("ZIP_TITLE", "制作 ZIP 压缩包");
define("ZIP_READY", "您的 ZIP 压缩包已准备好：");

// PROFILE.PHP
define("PROFILE_TITLE", "个人资料");
define("PROFILE_EXP_DONE", "个实验已完成，起始日期：");

// REGISTER-EXEC.PHP
define("REGISTER_USERNAME_USED", "用户名已被注册！");
define("REGISTER_EMAIL_USED", "已有人使用此 Email 地址！");
define("REGISTER_EMAIL_BODY", "您好,
有人在 eLabFTW 中注册了新的账号。请转到管理面板来激活账号！");
define("REGISTER_EMAIL_FAILED", "无法发送 Email 来通知管理员。错误已被记录。请直接联系管理员来验证您的账号。");
define("REGISTER_SUCCESS_NEED_VALIDATION", "注册成功 :)<br>您的账号需要通过管理员的验证。<br>验证通过后您将收到一封 Email。");
define("REGISTER_SUCCESS", "注册成功 :)<br>欢迎来到 eLabFTW \o/");

// REGISTER.PHP
define("REGISTER_TITLE", "注册");
define("REGISTER_LOGOUT", "请在注册另一个账号之前<a style='alert-link' href='logout.php'>注销</a>。");
define("REGISTER_BACK_TO_LOGIN", "返回登录页面");
define("REGISTER_H2", "创建您的账号");
define("REGISTER_DROPLIST", "------ 选择团队 ------");
define("REGISTER_CONFIRM_PASSWORD", "确认密码");
define("REGISTER_PASSWORD_COMPLEXITY", "密码强度");
define("REGISTER_BUTTON", "创建");

// RESET-EXEC.PHP
define("RESET_SUCCESS", "密码已重置。您现在可以登录了。");

// RESET-PASS.PHP
define("RESET_MAIL_SUBJECT", "[eLabFTW] 密码重置");
define("RESET_MAIL_BODY", "您好,
有人 (可能是您) 在以下 IP 地址："); 
define("RESET_MAIL_BODY2", "浏览器 UA 为：");
define("RESET_MAIL_BODY3", "在 eLabFTW 上请求了新的密码。

请根据以下链接的指示来重置密码：");
define("RESET_NOT_FOUND", "在数据库中未发现此 Email 地址！");

// REVISIONS.PHP
define("REVISIONS_TITLE", "历史版本");
define("REVISIONS_GO_BACK", "返回实验记录");
define("REVISIONS_LOCKED", "您不能恢复一个锁定的实验记录到历史版本！");
define("REVISIONS_CURRENT", "当前：");
define("REVISIONS_SAVED", "保存于：");
define("REVISIONS_RESTORE", "恢复");

// SEARCH.PHP
define("SEARCH", "搜索");
define("SEARCH_TITLE", "高级搜索");
define("SEARCH_BACK", "返回实验记录列表");
define("SEARCH_ONLY", "仅在下列用户的实验记录中搜索：");
define("SEARCH_YOU", "您");
define("SEARCH_EVERYONE", "在所有用户的实验记录中搜索");
define("SEARCH_IN", "搜索范围");
define("SEARCH_DATE", "日期范围");
define("SEARCH_AND", "至");
define("SEARCH_AND_TITLE", "标题中包含");
define("SEARCH_AND_BODY", "内容中包含");
define("SEARCH_AND_STATUS", "状态为");
define("SEARCH_SELECT_STATUS", "选择状态");
define("SEARCH_AND_RATING", "评级为");
define("SEARCH_STARS", "选择星标数量");
define("SEARCH_UNRATED", "为评级");
define("SEARCH_BUTTON", "启动搜索");
define("SEARCH_EXPORT", "导出此结果：");
define("SEARCH_SORRY", "抱歉，我什么也没找到 :(");

// SYSCONFIG.PHP
define("SYSCONFIG_TITLE", "eLabFTW 配置");
define("SYSCONFIG_TEAMS", "团队");
define("SYSCONFIG_SERVER", "服务器");
define("SYSCONFIG_TIMESTAMP", "时间戳");
define("SYSCONFIG_SECURITY", "安全性");
define("SYSCONFIG_1_H3_1", "添加一个新团队");
define("SYSCONFIG_1_H3_2", "编辑现有的团队");
define("SYSCONFIG_MEMBERS", "成员");
define("SYSCONFIG_ITEMS", "项目");
define("SYSCONFIG_CREATED", "已创建");
define("SYSCONFIG_2_H3", "底层设置");
define("SYSCONFIG_DEBUG", "激活 DEBUG 模式：");
define("SYSCONFIG_DEBUG_HELP", "激活后，\$_SESSION 和 \$_COOKIES 的内容将会被显示在管理员的页面底部。");
define("SYSCONFIG_PROXY", "代理服务器地址：");
define("SYSCONFIG_PROXY_HELP", "如果您处在防火墙 / 代理服务器之后，请在此处输入地址。例如：http://proxy.example.com:3128");
define("SYSCONFIG_PATH", "安装目录完整路径：");
define("SYSCONFIG_PATH_HELP", "这是安装路径的 MD5 值。除非您移动了一个已有的安装，否则您多半不需要更改它。");
define("SYSCONFIG_3_H3", "Universign 时间戳配置");
define("SYSCONFIG_STAMPSHARE", "团队可使用下列凭证来添加时间戳："); 
define("SYSCONFIG_STAMPSHARE_HELP", "您可以控制团队是否可以使用全局 Universign 账号。如果设置为<em>否</em>，团队管理员必须在管理面板添加登录信息。");
define("SYSCONFIG_STAMPLOGIN", "登录到外部时间戳服务：");
define("SYSCONFIG_STAMPLOGIN_HELP", "必须为 Email 地址。");
define("SYSCONFIG_STAMPPASS", "外部时间戳服务密码：");
define("SYSCONFIG_STAMPPASS_HELP", "此密码将会明文保存在数据库中！确保其不能登录其他账号…");
define("SYSCONFIG_4_H3", "安全性设置");
define("SYSCONFIG_ADMIN_VALIDATE", "用户注册后需要由管理员验证：");
define("SYSCONFIG_ADMIN_VALIDATE_HELP", "设为是来增加额外的安全性。");
define("SYSCONFIG_LOGIN_TRIES", "允许尝试登录次数：");
define("SYSCONFIG_LOGIN_TRIES_HELP", "3 次可能有点少。看看你自己需要几次 :)");
define("SYSCONFIG_BAN_TIME", "尝试登录失败后的屏蔽时间 (分钟)：");
define("SYSCONFIG_BAN_TIME_HELP", "我们使用 UA + IP 的 MD5 值来识别特定用户。因为仅基于 IP 地址肯定会引起麻烦。");
define("SYSCONFIG_5_H3", "SMTP 设置");
define("SYSCONFIG_5_HELP", "如果没有有效的途径来发送 Email，用户将无法重置密码。建议创建一个特定的 Mandrill.com (或 Gmail) 账号并在此添加其信息。");
define("SYSCONFIG_SMTP_ADDRESS", "SMTP 服务器地址：");
define("SYSCONFIG_SMTP_ADDRESS_HELP", "smtp.mandrillapp.com");
define("SYSCONFIG_SMTP_ENCRYPTION", "SMTP 加密方式 (可能是 TLS 或 STARTSSL)：");
define("SYSCONFIG_SMTP_ENCRYPTION_HELP", "您可以将此设置为 TLS 而不会引起问题。");
define("SYSCONFIG_SMTP_PORT", "SMTP 端口：");
define("SYSCONFIG_SMTP_PORT_HELP", "默认端口为 587。");
define("SYSCONFIG_SMTP_USERNAME", "SMTP 用户名：");
define("SYSCONFIG_SMTP_PASSWORD", "SMTP 密码：");

// TEAM.PHP
define("TEAM_TITLE", "团队");
define("TEAM_STATISTICS", "统计");
define("TEAM_TIPS_TRICKS", "小提示");
define("TEAM_BELONG", "您属于");
define("TEAM_TEAM", "团队");
define("TEAM_TOTAL_OF", "共有");
define("TEAM_EXP_BY", "个实验记录，由");
define("TEAM_DIFF_USERS", "位不同成员完成");
define("TEAM_ITEMS_DB", "个项目记录在数据库中。");
define("TEAM_TIP_1", "您可以通过按下 'T' 来使用任务列表");
define("TEAM_TIP_2", "您可以拥有自己的实验记录模版 (<a href='ucp.php?tab=3'>控制面板</a>)");
define("TEAM_TIP_3", "团队管理员可以编辑可用的状态和项目类型 (<a href='admin.php?tab=4'>管理面板</a>)");
define("TEAM_TIP_4", "如果您在编辑器中按下 Ctrl + Shift + D，日期将会出现在光标处");
define("TEAM_TIP_5", "您可以自定义快捷键 (<a href='ucp.php?tab=2'>控制面板</a>)");
define("TEAM_TIP_6", "您可以一键复制实验记录");
define("TEAM_TIP_7", "点击标签来列出所有具有此标签的项目");
define("TEAM_TIP_8", "在 <a href='https://www.universign.eu/en/timestamp'>Universign</a> 注册账号来为实验记录添加时间戳");
define("TEAM_TIP_9", "只有被锁定的实验记录可以添加时间戳");
define("TEAM_TIP_10", "一旦添加了时间戳，实验记录将不能解锁或编辑。只能对其添加评论。");

// TIMESTAMP.PHP
define("TIMESTAMP_CONFIG_ERROR", "时间戳功能未配置。请查看 <a class='alert-link' href='https://github.com/NicolasCARPi/elabftw/wiki/finalizing#setting-up-timestamping'>wiki</a>。");
define("TIMESTAMP_ERROR", "添加时间戳时发生错误。登录凭证可能有误或余额不足。");
define("TIMESTAMP_USER_ERROR", "添加时间戳时发生错误。实验记录未添加时间戳。错误已被记录。");
define("TIMESTAMP_SUCCESS", "实验记录已成功添加时间戳。已添加时间戳的实验记录可在下方下载。");

// UCP-EXEC.PHP
define("UCP_TITLE", "用户控制面板");
define("UCP_PASSWORD_SUCCESS", "密码已更新！");
define("UCP_PROFILE_UPDATED", "个人资料已更新");
define("UCP_ENTER_PASSWORD", "输入您的密码来编辑！");
define("UCP_PREFS_UPDATED", "您的偏好设置已更新。");
define("UCP_TPL_NAME", "您需要为模版指定一个名称！");
define("UCP_TPL_SHORT", "模版名称最少为 3 个字符。");
define("UCP_TPL_SUCCESS", "已成功添加实验记录模版。");
define("UCP_TPL_EDITED", "已成功编辑实验记录模版。");
define("UCP_TPL_PLACEHOLDER", "模版名称");
define("UCP_ACCOUNT", "账号");
define("UCP_PREFERENCES", "偏好");
define("UCP_TPL", "模版");
define("UCP_H4_1", "修改您的个人信息");
define("UCP_H4_2", "修改您的身份");
define("UCP_H4_3", "修改您的密码");
define("UCP_NEWPASS", "新密码");
define("UCP_CNEWPASS", "确认新密码");
define("UCP_H4_4", "修改您的联系信息");
define("UCP_BUTTON_1", "更新个人资料");
define("UCP_H3_1", "显示");
define("UCP_DEFAULT", "默认");
define("UCP_COMPACT", "紧凑");
define("UCP_ORDER_BY", "排序依据：");
define("UCP_ITEM_ID", "项目 ID");
define("UCP_WITH", "同时");
define("UCP_NEWER", "新项目优先");
define("UCP_OLDER", "旧项目优先");
define("UCP_LIMIT", "每页显示项目数：");
define("UCP_H3_2", "键盘快捷键");
define("UCP_H3_3", "警告");
define("UCP_CLOSE_WARNING", "关闭编辑窗口 / 标签页前显示警告？");
define("UCP_CREATE_NEW", "新建");
define("UCP_ADD_TPL", "添加模版");
define("UCP_EDIT_BUTTON", "编辑模版");
define("LANGUAGE", "语言");

// VIEW DB
define("NOTHING_TO_SHOW", "此 ID 无可显示内容。");
define("LAST_MODIFIED_BY", "由");
define("ON", "最后修改于");

// VIEW XP
define("VIEW_XP_FORBIDDEN", "<strong>禁止访问：</strong>此实验记录的可见性被设置为“仅所有者可见”。");
define("VIEW_XP_RO", "<strong>只读模式：</strong>此实验记录所有者为");
define("VIEW_XP_TIMESTAMPED", "实验记录已被");
define("AT", "添加时间戳于");
define("VIEW_XP_ELABID", "唯一 eLabID：");
define("COMMENTS", "评论");
define("ADD_COMMENT", "添加评论");
define("DELETE_THIS", "删除？"); // in JS
define("CONFIRM_STAMP", "一旦添加时间戳，实验记录将不能再被编辑！您确定要执行此操作吗？"); // in JS

// TAGCLOUD
define("TAGCLOUD_H4", "标签云");
define("NOT_ENOUGH_TAGS", "没有足够的标签来创建标签云。");

// STATISTICS
define("STATISTICS_H4", "统计");
define("STATISTICS_NOT_YET", "无统计数据可用。");
define("STATISTICS_EXP_FOR", "实验记录");

// FILE UPLOAD
define("FILE_UPLOAD_H3", "添加文件。");
define("FILE_START_UPLOAD", "开始上传");

// SHOW DB
define("SHOW_DB_CREATE_NEW", "新建");
define("SHOW_DB_FILTER_TYPE", "选择类型");
define("FOUND", "找到");
define("RESULTS", "个结果。");
define("FOUND_1", "找到 1 个结果。");
define("FOUND_0", "什么也没找到。");
define("SHOW_DB_WELCOME", "<strong>欢迎来到 eLabFTW。</strong>在 «新建» 列表中选择一项开始建立您的数据库。");
define("SHOW_DB_LAST_10", "显示最近 10 条记录：");

// SHOW XP
define("SHOW_XP_MORE", "Load more");
define("SHOW_XP_CREATE", "创建实验记录");
define("SHOW_XP_CREATE_TPL", "从模版创建");
define("SHOW_XP_FILTER_STATUS", "选择状态");
define("SHOW_XP_NO_TPL", "<strong>您还没有模版。</strong>转到<a  class='alert-link' href='ucp.php?tab=3'>您的控制面板</a>来创建一个！");
define("SHOW_XP_NO_EXP", "<strong>欢迎来到 eLabFTW。</strong>点击<img src='img/add.png' alt='创建实验记录' /><a class='alert-link' href='create_item.php?type=exp'>创建实验记录</a>按钮开始记录您的实验。");

// EDIT DB
define("LOCKED_NO_EDIT", "<strong>此项目已被锁定。</strong>您不能编辑它。");
define("TAGS", "标签");
define("CLOSE_WARNING", "您想离开此页面吗？所有未保存的更改将会丢失！");

// EDIT XP
define("EDIT_XP_NO_RIGHTS", "<strong>无法编辑：</strong>此实验记录不属于您！");
define("EDIT_XP_TAGS_HELP", "点击一个标签来移除它");
define("EDIT_XP_ADD_TAG", "添加标签");
define("ONLY_THE_TEAM", "仅团队成员");
define("ONLY_ME", "只有我");
define("EXPERIMENT", "实验记录");
define("LINKED_ITEMS", "链接的项目");
define("ADD_LINK", "添加链接");
define("ADD_LINK_PLACEHOLDER", "从数据库添加");
define("REVISION_AVAILABLE", "个历史版本可用。");
define("REVISIONS_AVAILABLE", "个历史版本可用。");
define("SHOW_HISTORY", "显示历史");

// INC/HEAD.PHP
define("LOGGED_IN_AS", "你好，");
define("SETTINGS", "设置");
define("LOGOUT", "注销");

// INC/FOOTER.PHP
define("CHECK_FOR_UPDATES", "检查更新");
define("SYSADMIN_PANEL", "系统管理面板");
define("ADMIN_PANEL", "管理面板");
define("POWERED_BY", "Powered by");
define("PAGE_GENERATED", "Page generated in");
