import "./modules/admin-qaform";
import "./modules/admin-bar-menu-self-destruct";

// eslint-disable-next-line no-undef
jQuery(($) => {
	// 強制表示のお知らせが未読なら出力
	const shouldReadCount = window.n2?.notifications_should_read || 0;
	if (shouldReadCount > 0) {
		const href = "admin.php?page=n2_notification_read";
		$("#wpwrap").append(`
            <a href="${href}" id="should-read-notification" class="should-read-notification">
                <span class="should-read-notification-text">
                    新しいお知らせがあります
                </span>
            </a>
        `);
	}
});
