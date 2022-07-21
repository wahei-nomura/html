import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * アレルギー項目制御
	 * 
	================================================================== */
	jQuery(function ($) {
		class ControlAllergen {
			public food: boolean;
			public allergen: boolean;

			constructor(food: boolean = false, allergen: boolean = false) {
				this.food = food;
				this.allergen = allergen;
			}

			get foodBool() {
				return this.food;
			}

			set foodBool(food: boolean) {
				this.food = food;
			}

			get allergenBool() {
				return this.allergen;
			}

			set allergenBool(allergen: boolean) {
				this.allergen = allergen;
			}

		}

		const allergenState=new ControlAllergen();

		console.log($('input[name="食品確認[]"]:checked').val())

		const allergenBoolContent = $("label[for='アレルギー有無確認']")
			.parent()
			.parent();
		const allergenListContent = $("label[for='アレルゲン']").parent().parent();
		const tokkiContent = $("#アレルギーの特記事項").parent().parent();
		allergenBoolContent.css("background-color", "pink");
		allergenListContent.css("background-color", "yellow");
		tokkiContent.css("background-color", "skyblue");

		// allergenBoolContent.css("display", "none");
		// allergenListContent.css("display", "none");
		// tokkiContent.css("display", "none");
	});
};
