if(window.jQuery) {
    jQuery(document).ready(function () {
        let tsk = document.querySelector('[name="learn_press_stripe[test_secret_key]"]');
        let tpk = document.querySelector("[name='learn_press_stripe[test_publishable_key]']");
        let lsk = document.querySelector("[name='learn_press_stripe[live_secret_key]']");
        let lpk = document.querySelector("[name='learn_press_stripe[live_publishable_key]']");
        if (tsk && tpk && lsk && lpk) {
            tsk.type = "password"; // hide secrets
            lsk.type = "password";
        }
    });
}