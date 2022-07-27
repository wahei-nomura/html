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
				if (!food) {
					this.allergenBool = false;
				}
			}

			get allergenBool() {
				return this.allergen;
			}

			set allergenBool(allergen: boolean) {
				this.allergen = allergen;
			}

			public displayAllergenBool() {
				const allergenBoolBlock = $(
					'label[for="アレルギー有無確認"],label[for="アレルギーの特記事項"]'
				)
					.parent()
					.parent();

				allergenBoolBlock
					.prev()
					.css("display", `${this.food ? "block" : "none"}`);

				allergenBoolBlock.css(
					"display",
					`${this.food ? "block" : "none"}`
				);
				allergenBoolBlock
					.next()
					.css("display", `${this.food ? "block" : "none"}`);

				$('input[name="アレルギー有無確認[]"]')
					.val("アレルギー品目あり")
					.prop("checked", this.food && this.allergen);
			}

			public displayAllergenList() {
				const allergenListBlock = $('label[for="アレルゲン"]')
					.parent()
					.parent();

				allergenListBlock
					.prev()
					.css("display", `${this.allergen ? "block" : "none"}`);

				allergenListBlock.css(
					"display",
					`${this.allergen ? "block" : "none"}`
				);

				allergenListBlock
					.next()
					.css("display", `${this.allergen ? "block" : "none"}`);

				$.each($('input[name="アレルゲン[]"]'), (i, v) => {
					$(v).prop("checked", this.allergen && $(v).prop("checked"));
				});
			}
		}

		// インスタンス生成
		const allergenState = new ControlAllergen(
			$('input[name="食品確認[]"]').val("食品である").prop("checked"),
			$('input[name="アレルギー有無確認[]"]')
				.val("アレルギー品目あり")
				.prop("checked")
		);
		// 初期表示
		allergenState.displayAllergenBool();
		allergenState.displayAllergenList();

		// 	checkboxイベント
		$('input[name="食品確認[]"]').on("change", (e) => {
			allergenState.foodBool = $('input[name="食品確認[]"]')
				.val("食品である")
				.prop("checked");
			allergenState.displayAllergenBool();

			allergenState.allergenBool = $('input[name="アレルギー有無確認[]"]')
				.val("アレルギー品目あり")
				.prop("checked");
			allergenState.displayAllergenList();
		});

		$('input[name="アレルギー有無確認[]"]').on("change", (e) => {
			allergenState.allergenBool = $('input[name="アレルギー有無確認[]"]')
				.val("アレルギー品目あり")
				.prop("checked");
			allergenState.displayAllergenList();
		});

	});
};
