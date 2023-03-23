jQuery(document).ready(function () {
  function addHiddenClass(id, className) {
    jQuery(`#woocommerce_tunl_${id}`)
      .parent()
      .parent()
      .parent()
      .addClass(`${className}_tunl_class`);
  }

  function showLive() {
    jQuery(".sandbox_tunl_class").hide();
    jQuery(".live_tunl_class").show();
  }

  function showSandbox() {
    jQuery(".live_tunl_class").hide();
    jQuery(".sandbox_tunl_class").show();
  }

  addHiddenClass("username", "sandbox");
  addHiddenClass("password", "sandbox");

  addHiddenClass("live_username", "live");
  addHiddenClass("live_password", "live");

  const testModeEnabled = jQuery("#woocommerce_tunl_api_mode").prop("checked");
  testModeEnabled ? showSandbox() : showLive();

  toastr.options = {
    closeButton: false,
    debug: false,
    newestOnTop: false,
    progressBar: false,
    positionClass: "toast-top-right",
    preventDuplicates: false,
    onclick: null,
    showDuration: "300",
    hideDuration: "1000",
    timeOut: "3000",
    extendedTimeOut: "1000",
    showEasing: "swing",
    hideEasing: "linear",
    showMethod: "fadeIn",
    hideMethod: "fadeOut",
  };

  function disableAutoComplete(selector) {
    jQuery(selector).attr("autocomplete", "new-password");
  }

  disableAutoComplete("#woocommerce_tunl_password");

  const loader = `<img src="${adminAjax.ajaxloader}" class="loader-connect-class" />`;

  const authButton = `<a class="btn button-primary btn-connect-payment">Connect</a>`;
  const disconnectBtn = `<a class="btn button-primary btn-disconnect-payment">Disconnect</a>`;
  const authButtonElm = jQuery(authButton).hide();
  const disconnectElm = jQuery(disconnectBtn).hide();

  const buttonsAndLoaderSection = jQuery(
    `<div class="connect-btn-section">${loader}</div>`
  );
  buttonsAndLoaderSection.append(authButtonElm);
  buttonsAndLoaderSection.append(disconnectElm);

  const tunlConnectBtn = jQuery("#woocommerce_tunl_connect_button");
  const isAuthenticated =
    tunlConnectBtn.val() == 1 || tunlConnectBtn.val() == "";
  const formInp = tunlConnectBtn.parents(".forminp");
  formInp.append(buttonsAndLoaderSection);

  const tokenParent = jQuery("#woocommerce_tunl_tunl_token").parent();
  const connectedStatus = jQuery(
    `<a class="btn-connected" >Ready</a>`
  ).hide();
  const disconnectedStatus = jQuery(`<a >Not Ready</a>`).hide();

  tokenParent.append(connectedStatus);
  tokenParent.append(disconnectedStatus);

  jQuery(document).on("change", "#woocommerce_tunl_api_mode", function () {
    const testModeEnabled = jQuery(this).prop("checked");
    testModeEnabled ? showSandbox() : showLive();
    showAuthButton();
    tunlConnectBtn.val("1");
  });

  const showAuthButton = () =>
    connectedStatus.hide() &&
    disconnectedStatus.show() &&
    authButtonElm.show() &&
    disconnectElm.hide();
  const showDisconnect = () =>
    connectedStatus.show() &&
    disconnectedStatus.hide() &&
    authButtonElm.hide() &&
    disconnectElm.show();
  isAuthenticated ? showAuthButton() : showDisconnect();

  function showLoader(type) {
    jQuery(`.loader-connect-class`).show();
    jQuery(`.btn-${type}-payment`).css("pointer-events", "none");
    jQuery(`.btn-${type}-payment`).css("opacity", "0.5");
  }

  function hideLoader(type) {
    jQuery(`.loader-connect-class`).hide();
    jQuery(`.btn-${type}-payment`).css("pointer-events", "unset");
    jQuery(`.btn-${type}-payment`).css("opacity", "1");
  }

  function reloadPage() {
    setTimeout(function () {
      location.reload();
    }, 1000);
  }

  function post(data, loader, reload = true) {
    showLoader(loader);
    jQuery.ajax({
      type: "POST",
      url: adminAjax.ajaxurl,
      data,
      success: function (response) {
        if (!response.status) {
          hideLoader(loader);
          return toastr["error"](response.message);
        }
        toastr["success"](response.message);
        reload && reloadPage();
      },
    });
  }

  jQuery(document).on("click", ".btn-connect-payment", function () {
    const demoUser = jQuery("#woocommerce_tunl_username").val();
    const demoPass = jQuery("#woocommerce_tunl_password").val();
    const liveUser = jQuery("#woocommerce_tunl_live_username").val();
    const livePass = jQuery("#woocommerce_tunl_live_password").val();

    const testMode = jQuery("#woocommerce_tunl_api_mode").is(":checked");
    const api_mode = testMode ? "yes" : "no";

    const enabled = jQuery("#woocommerce_tunl_enabled").is(":checked");
    const tunl_enabled = enabled ? "yes" : "no";

    const username = testMode ? demoUser : liveUser;
    const password = testMode ? demoPass : livePass;
    if (!username) return toastr["error"]("Please enter API Key!");
    if (!password) return toastr["error"]("Please enter Secret Key!");

    var tunl_title = jQuery("#woocommerce_tunl_title").val();

    const data = {
      action: "connect_tunl_payment",
      tunl_title,
      username,
      password,
      api_mode,
      tunl_enabled,
    };

    post(data, "connect");

    window.onbeforeunload = null;
  });

  jQuery(document).on("click", ".btn-disconnect-payment", function () {
    const testMode = jQuery("#woocommerce_tunl_api_mode").is(":checked");
    const api_mode = testMode ? "yes" : "no";
    const data = { action: "disconnect_tunl_payment", api_mode };
    post(data, "disconnect");
  });
});
