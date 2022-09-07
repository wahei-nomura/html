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

				allergenBoolBlock.css({
					visibility: `${this.food ? "visible" : "hidden"}`,
					opacity: `${this.food ? 1 : 0}`,
					height: `${this.food ? "auto" : 0}`,
					transition: ".3s",
				});

				$('input[name="アレルギー有無確認[]"]')
					.val("アレルギー品目あり")
					.prop("checked", this.food && this.allergen);
			}

			public displayAllergenList() {
				const allergenListBlock = $('label[for="アレルゲン"]')
					.parent()
					.parent();

				allergenListBlock.css({
					visibility: `${this.allergen ? "visible" : "hidden"}`,
					opacity: `${this.allergen ? 1 : 0}`,
					height: `${this.allergen ? "auto" : 0}`,
					transition: ".3s",
				});

				$.each($('input[name="アレルゲン[]"]'), (i, v) => {
					$(v).prop("checked", this.allergen && $(v).prop("checked"));
				});
			}
		}

		$.ajax({
			url: ajaxUrl(window),
			data: {
				action: "N2_Setpost",
			},
		}).done((res) => {
			// 事業者の食品取り扱いパラメーターが「有」の時のみ新規ページで食品にデフォルトチェック
			if (
				JSON.parse(res).food_param === "有" &&
				location.href.match(/post-new\.php/)
			) {
				$('input[name="食品確認[]"]')
					.val("食品である")
					.prop("checked", true);
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
				if (
					!$(e.target).val("食品である").prop("checked") &&
					!confirm(
						"このチェックを外すと入力中のアレルギーに関するものが消えますがよろしいですか？"
					)
				) {
					$(e.target).val("食品である").prop("checked",true)
					return;
				}
				allergenState.foodBool = $('input[name="食品確認[]"]')
					.val("食品である")
					.prop("checked");
				allergenState.displayAllergenBool();

				allergenState.allergenBool = $(
					'input[name="アレルギー有無確認[]"]'
				)
					.val("アレルギー品目あり")
					.prop("checked");
				allergenState.displayAllergenList();
			});

			$('input[name="アレルギー有無確認[]"]').on("change", (e) => {
				allergenState.allergenBool = $(
					'input[name="アレルギー有無確認[]"]'
				)
					.val("アレルギー品目あり")
					.prop("checked");
				allergenState.displayAllergenList();
			});
		});
	});
};
