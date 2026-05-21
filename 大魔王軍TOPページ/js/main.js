
// debounce関数
function debounce(func, wait) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        var later = function() {
            timeout = null;
            func.apply(context, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}


// menu
$(window).on("load resize", debounce(function() {

			//小さな端末用
			if(window.innerWidth < 900) {	// ※ブレイクポイント指定箇所
				$('body').addClass('s').removeClass('p');
				$('#menubar').addClass('d-n').removeClass('d-b');
				$('#menubar_hdr').removeClass('d-n ham').addClass('d-b');
				
			//大きな端末用
			} else {
				$('body').addClass('p').removeClass('s');
				$('#menubar').addClass('d-b').removeClass('d-n');
				$('#menubar_hdr').removeClass('d-b').addClass('d-n');
			}

}, 5));


//ハンバーガーメニューをクリックした際の処理
$(function() {
	$('#menubar_hdr').click(function() {
		$(this).toggleClass('ham');

			if($(this).hasClass('ham')) {
				$('#menubar').addClass('d-b');
			} else {
				$('#menubar').removeClass('d-b');
			}

	});
});


// 同一ページへのリンクの場合に開閉メニューを閉じる処理
$(function() {
	$('#menubar a[href^="#"]').click(function() {
		$('#menubar').removeClass('d-b');
		$('#menubar_hdr').removeClass('ham');
	});
});


//ドロップダウンの親liタグ
$(function() {
    $('#menubar a[href=""]').click(function() {
		return false;
    });
});


//ドロップダウンメニューの処理
$(function() {

	$('#menubar li:has(ul)').addClass('ddmenu_parent');
	$('.ddmenu_parent > a').addClass('ddmenu');

		//タッチデバイス用
		$('.ddmenu').on('touchstart', function() {
			$(this).next('ul').stop().slideToggle();
			$('.ddmenu').not(this).next('ul').slideUp();
			return false;
		});

		//PC用
		$('.ddmenu_parent').hover(function() {
			$(this).children('ul').stop().slideDown();
		}, function() {
			$(this).children('ul').stop().slideUp();
		});

});


//ドロップダウンをページ内リンクで使った場合に、ドロップダウンを閉じる。
$(function() {
	$('.ddmenu_parent ul a').click(function() {
		$('.ddmenu_parent ul').slideUp();
	});
});


// スムーススクロール※ヘッダーの高さとマージンを取得する場合。
$(function() {
    var headerHeight = $('header').outerHeight();
    var headerMargin = parseInt($('header').css("margin-top"));
    var totalHeaderHeight = headerHeight + headerMargin;
    var topButton = $('.pagetop'); // ページ上部に戻るボタンのセレクター
    var scrollShow = 'pagetop-show'; // ボタン表示用のクラス

    // スムーススクロールを実行する関数
    function smoothScroll(target) {
        var scrollTo = target === '#' ? 0 : $(target).offset().top - totalHeaderHeight;
        $('html, body').animate({scrollTop: scrollTo}, 500);
    }

    // ページ内リンク & ページトップへのスムーススクロール
    $('a[href^="#"], .pagetop').click(function(e) {
        e.preventDefault();
        var id = $(this).attr('href') || '#';
        smoothScroll(id);

        // スクロール後のハッシュ更新を適用
        if (id !== '#') {
            setTimeout(function() {
                window.location.hash = id;
            }, 100);
        }
    });

    // ボタンの表示/非表示
    $(topButton).hide();
    $(window).scroll(function() {
        if($(this).scrollTop() >= 300) {
            $(topButton).fadeIn().addClass(scrollShow);
        } else {
            $(topButton).fadeOut().removeClass(scrollShow);
        }
    });

    // ページロード時にURLのハッシュが存在する場合のスムーススクロール
    if(window.location.hash) {
        $('html, body').scrollTop(0); // ページの最上部に即時スクロール
        setTimeout(function() {
            smoothScroll(window.location.hash);
        }, 10);
    }
});


// 汎用開閉処理
$(function() {
	$('.openclose').next().hide();
	$('.openclose').click(function() {
		$(this).next().slideToggle();
		$('.openclose').not(this).next().slideUp();
	});
});


//===============================================================
// form1用
//===============================================================
(function() {
    "use strict";

    function initAjaxForm(form) {
        var actionUrl = form.getAttribute("action") || "form.php";
        var submitButton = form.querySelector('button[type="submit"]');
        var globalErrorBox = findSiblingBox(form, ".form-global-error");
        var successBox = findSiblingBox(form, ".form-success-box");
        var privacyRow = form.querySelector(".js-privacy-row");
        var attachmentRow = form.querySelector(".js-attachment-row");
        var attachmentInput = attachmentRow ? attachmentRow.querySelector('input[type="file"]') : null;
        var csrfInput = form.querySelector('input[name="csrf_token"]');

        if (!submitButton || !csrfInput) {
            return;
        }

        initDateSelectGroups(form);
        fetchInitData();

        form.addEventListener("submit", function(event) {
            event.preventDefault();

            /* ローカルの通常プレビュー（file://）では送信しない */
            if (window.location.protocol === "file:") {
                return;
            }

            clearErrors();
            hideGlobalError();

            submitButton.disabled = true;

            var hadSelectedFile = attachmentInput && attachmentInput.files && attachmentInput.files.length > 0;
            var formData = new FormData(form);

            fetch(actionUrl, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(json) {
                if (json && json.csrfToken) {
                    csrfInput.value = json.csrfToken;
                }

                if (json && json.ok) {
                    showSuccess();
                    return;
                }

                resetAttachmentOnError(hadSelectedFile);

                if (json && json.fieldErrors) {
                    applyFieldErrors(json.fieldErrors);
                }

                if (json && json.formError) {
                    showGlobalError(json.formError);
                }
            })
            .catch(function() {
                resetAttachmentOnError(false);
                showGlobalError("送信に失敗しました。時間をおいて再度お試しください。");
            })
            .finally(function() {
                submitButton.disabled = false;
            });
        });

        function fetchInitData() {
            /* ローカルの通常プレビュー（file://）では初期化通信を行わず、HTML側のプレビュー設定を使う */
            if (window.location.protocol === "file:") {
                var previewPrivacy = form.getAttribute("data-preview-privacy") === "true";
                var previewAttachment = form.getAttribute("data-preview-attachment") === "true";

                togglePrivacyRow(previewPrivacy);
                toggleAttachmentRow(previewAttachment);
                return;
            }

            fetch(actionUrl + "?action=init", {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(json) {
                if (!json || !json.ok) {
                    showGlobalError("フォームの初期化に失敗しました。ページを再読み込みしてください。");
                    return;
                }

                if (json.csrfToken) {
                    csrfInput.value = json.csrfToken;
                }

                togglePrivacyRow(json.privacyConsentEnabled !== false);
                toggleAttachmentRow(json.attachmentEnabled !== false);
            })
            .catch(function() {
                showGlobalError("フォームの初期化に失敗しました。ページを再読み込みしてください。");
            });
        }

        function togglePrivacyRow(isEnabled) {
            if (!privacyRow) {
                return;
            }

            privacyRow.hidden = !isEnabled;

            var privacyInput = privacyRow.querySelector('input[name="privacy_consent"]');
            if (!privacyInput) {
                return;
            }

            if (!isEnabled) {
                privacyInput.checked = false;
            }

            privacyInput.disabled = !isEnabled;
        }

        function toggleAttachmentRow(isEnabled) {
            if (!attachmentRow) {
                return;
            }

            attachmentRow.hidden = !isEnabled;

            if (!attachmentInput) {
                return;
            }

            if (!isEnabled) {
                attachmentInput.value = "";
            }

            attachmentInput.disabled = !isEnabled;
        }

        function clearErrors() {
            var rows = form.querySelectorAll(".form-row");
            rows.forEach(function(row) {
                row.classList.remove("is-error");

                row.querySelectorAll(".form-error-text, .form-file-reset-text").forEach(function(message) {
                    message.remove();
                });
            });
        }

        function applyFieldErrors(fieldErrors) {
            var firstRow = null;
            var firstInput = null;

            Object.keys(fieldErrors).forEach(function(fieldName) {
                var input = form.querySelector('[name="' + cssEscape(fieldName) + '"]') ||
                            form.querySelector('[name="' + cssEscape(fieldName) + '[]"]');
                var row = input ? input.closest(".form-row") : null;

                if (!row) {
                    row = form.querySelector('.js-date-select-group[data-field-name="' + cssEscape(fieldName) + '"]');
                    if (row) {
                        input = row.querySelector("select, input, textarea");
                    }
                }

                if (!row) {
                    return;
                }

                row.classList.add("is-error");

                var error = document.createElement("p");
                error.className = "form-error-text";
                error.textContent = fieldErrors[fieldName];
                row.appendChild(error);

                if (!firstRow) {
                    firstRow = row;
                    firstInput = input || null;
                }
            });

            if (firstInput && typeof firstInput.focus === "function") {
                try {
                    firstInput.focus({ preventScroll: true });
                } catch (error) {
                    firstInput.focus();
                }
            }

            if (firstRow) {
                firstRow.scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });
            }
        }

        function resetAttachmentOnError(hadSelectedFile) {
            if (!attachmentRow || !attachmentInput) {
                return;
            }

            if (attachmentInput.value !== "") {
                attachmentInput.value = "";
            }

            if (!hadSelectedFile) {
                return;
            }

            var note = document.createElement("p");
            note.className = "form-file-reset-text";
            note.textContent = "安全のため、添付ファイルは再選択してください。";
            attachmentRow.appendChild(note);
        }

        function showGlobalError(message) {
            if (!globalErrorBox) {
                return;
            }

            globalErrorBox.textContent = message;
            globalErrorBox.hidden = false;
            globalErrorBox.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });
        }

        function hideGlobalError() {
            if (!globalErrorBox) {
                return;
            }

            globalErrorBox.hidden = true;
            globalErrorBox.textContent = "";
        }

        function showSuccess() {
            form.hidden = true;
            hideGlobalError();

            if (successBox) {
                successBox.hidden = false;
                successBox.scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });
            }
        }
    }

    function initDateSelectGroups(scope) {
        var groups = scope.querySelectorAll(".js-date-select-group");

        groups.forEach(function(group) {
            var mode = group.getAttribute("data-date-mode");
            var yearSelect = group.querySelector('[data-date-part="year"]');
            var monthSelect = group.querySelector('[data-date-part="month"]');
            var daySelect = group.querySelector('[data-date-part="day"]');
            var startYear = parseInt(group.getAttribute("data-year-start"), 10);
            var endYear = parseInt(group.getAttribute("data-year-end"), 10);

            if (monthSelect) {
                populateNumberSelect(monthSelect, 1, 12, "月を選択", "月");
            }

            if (yearSelect) {
                if (isNaN(startYear)) {
                    startYear = 1900;
                }
                if (isNaN(endYear)) {
                    endYear = new Date().getFullYear();
                }

                populateNumberSelect(yearSelect, startYear, endYear, "年を選択", "年", true);
            }

            if (daySelect) {
                updateDayOptions();
            }

            if (monthSelect) {
                monthSelect.addEventListener("change", updateDayOptions);
            }

            if (yearSelect && mode === "ymd") {
                yearSelect.addEventListener("change", updateDayOptions);
            }

            function updateDayOptions() {
                if (!daySelect) {
                    return;
                }

                var maxDay = 31;
                var month = monthSelect ? parseInt(monthSelect.value, 10) : NaN;
                var year = yearSelect ? parseInt(yearSelect.value, 10) : new Date().getFullYear();

                if (!isNaN(month)) {
                    if (isNaN(year)) {
                        year = new Date().getFullYear();
                    }
                    maxDay = new Date(year, month, 0).getDate();
                }

                populateNumberSelect(daySelect, 1, maxDay, "日を選択", "日");
            }
        });
    }

    function populateNumberSelect(select, start, end, placeholder, suffix, descending) {
        if (!select) {
            return;
        }

        var currentValue = select.value;
        var numbers = [];
        var i;

        if (descending) {
            for (i = end; i >= start; i -= 1) {
                numbers.push(i);
            }
        } else {
            for (i = start; i <= end; i += 1) {
                numbers.push(i);
            }
        }

        select.innerHTML = "";

        var placeholderOption = document.createElement("option");
        placeholderOption.value = "";
        placeholderOption.textContent = placeholder;
        select.appendChild(placeholderOption);

        numbers.forEach(function(number) {
            var option = document.createElement("option");
            option.value = String(number);
            option.textContent = String(number) + suffix;
            select.appendChild(option);
        });

        if (currentValue !== "" && hasOptionValue(select, currentValue)) {
            select.value = currentValue;
        } else {
            select.value = "";
        }
    }

    function hasOptionValue(select, value) {
        var options = Array.prototype.slice.call(select.options);
        return options.some(function(option) {
            return option.value === value;
        });
    }

    function findSiblingBox(form, selector) {
        var parent = form.parentElement;
        if (!parent) {
            return null;
        }
        return parent.querySelector(selector);
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === "function") {
            return window.CSS.escape(value);
        }
        return String(value).replace(/"/g, '\\"');
    }

    document.addEventListener("DOMContentLoaded", function() {
        var forms = document.querySelectorAll(".js-ajax-form");
        forms.forEach(initAjaxForm);
    });
})();
