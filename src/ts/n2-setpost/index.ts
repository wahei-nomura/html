import main from "./main";
import price from "./price";
import rakutenTag from "./rakuten-tag";
import mediaUp from "./media-up";
import syncStatus from "./sync-status";
import food from "./food";

export default () => {
	main();
	price();
	// rakutenTag();
	mediaUp();
	syncStatus();
	food();
};
