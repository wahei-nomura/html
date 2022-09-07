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

			public displayKigen() {
				const kigenBlock = $(
					'label[for="賞味期限"],label[for="消費期限"]'
				)
					.parent()
					.parent();

				kigenBlock.css({
					visibility: `${this.food ? "visible" : "hidden"}`,
					opacity: `${this.food ? 1 : 0}`,
					height: `${this.food ? "auto" : 0}`,
					transition: ".3s",
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
			const foodState = new ControlFood(
				$('input[name="食品確認[]"]').val("食品である").prop("checked"),
				$('input[name="アレルギー有無確認[]"]')
					.val("アレルギー品目あり")
					.prop("checked")
			);
			// 初期表示
			foodState.displayAllergenBool();
			foodState.displayAllergenList();
			foodState.displayKigen();

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
