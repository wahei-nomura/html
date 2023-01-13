import { prefix, neoNengPath, ajaxUrl } from "../functions/index";

export default () => {
	/** ===============================================================
	 * 
	 * アレルギー項目制御
	 * 
	================================================================== */
	jQuery(function ($) {
		class ControlFood {
			public food: boolean;
			public allergen: boolean;

			constructor(food: boolean = false, allergen: boolean = false) {
				this.food = food;
				this.allergen = allergen;

				// 初期表示
				this.displayAllergenBool();
				this.displayAllergenList();
				this.displayKigen();
				this.displaySanchi();

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
					'#アレルギー有無確認, #アレルギーの特記事項'
				)

				allergenBoolBlock.css(this.blockCss(this.food));

				$('input[name="アレルギー有無確認[]"]')
					.val("アレルギー品目あり")
					.prop("checked", this.food && this.allergen);
			}

			public displayAllergenList() {
				const allergenListBlock = $('#アレルゲン')
				allergenListBlock.css(this.blockCss(this.allergen));

				$.each($('input[name="アレルゲン[]"]'), (i, v) => {
					$(v).prop("checked", this.allergen && $(v).prop("checked"));
				});
			}

			public displayKigen() {
				const kigenBlock = $(
					'#賞味期限, #消費期限'
				)

				kigenBlock.css(this.blockCss(this.food));
			}

			public displaySanchi() {
				const sanchiBlock = $(
					'#原料原産地, #加工地'
				)

				sanchiBlock.css(this.blockCss(this.food));
			}

			private blockCss(pattern: Boolean) {
				return {
					display: `${pattern ? "revert" : "none"}`,
					animation: `${pattern ? "appear .5s ease" : ""}`,
				}
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
			const foodState = new ControlFood(
				$('input[name="食品確認[]"]').val("食品である").prop("checked"),
				$('input[name="アレルギー有無確認[]"]')
					.val("アレルギー品目あり")
					.prop("checked")
			);

			// 	checkboxイベント
			$('input[name="食品確認[]"]').on("change", (e) => {
				if (
					!$(e.target).val("食品である").prop("checked") &&
					!confirm(
						"このチェックを外すとアレルギーのチェックもリセットされますがよろしいですか？"
					)
				) {
					$(e.target).val("食品である").prop("checked", true);
					return;
				}
				foodState.foodBool = $('input[name="食品確認[]"]')
					.val("食品である")
					.prop("checked");
				foodState.displayKigen();
				foodState.displaySanchi();
				foodState.displayAllergenBool();

				foodState.allergenBool = $(
					'input[name="アレルギー有無確認[]"]'
				)
					.val("アレルギー品目あり")
					.prop("checked");
				foodState.displayAllergenList();
			});

			$('input[name="アレルギー有無確認[]"]').on("change", (e) => {
				foodState.allergenBool = $(
					'input[name="アレルギー有無確認[]"]'
				)
					.val("アレルギー品目あり")
					.prop("checked");
				foodState.displayAllergenList();
			});
		});
	});
};
