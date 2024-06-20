jQuery(function($) {
    $('.pilih-bayar').click(function(){
        if($(this).attr("value")=="bank"){
            $("#bankOptions").show();
        } else {
            $("#bankOptions").hide();
        }
    });
    $('.nominal-donasi').on('keyup', function(){
        var angka1 = parseFloat($(this).val()) || 0;
        var angka2 = parseFloat($('#kodeunik').val()) || 0;
        var hasil = angka1 + angka2;
        $('.total-donasi').html(DonasiCurrency(hasil));
        $('#total-donasi').val(hasil);
    });
    $('.form-donasi').on('submit', function (e) {
        e.preventDefault();
        if ($('.form-donasi textarea[name="g-recaptcha-response"]').length) {
            var captc = $('.form-donasi textarea[name="g-recaptcha-response"]').val();
        } else {
            var captc = 1;
        }
        if(captc){
          var datas = $('.form-donasi').serialize();
            $(".loadd").html('<span class="ms-1 spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            $.ajax({
                type: "POST",
                data: "action=submitdonasi&"+datas,
                url: vdonasi_ajax_object.ajax_url,
                success:function(data) {
                    $(".hasil-donasi").html('<div class="col-12 mx-0">'+data+'</div>');
                    /*
                    setTimeout(function() {
                        window.location.reload();
                    }, 4000);
                    */
                }
            });
        } else {
            alert('Silahkan Isi Captcha!');
        }
    });
});


function DonasiCurrency(number) {
    // Cek apakah input adalah angka atau bisa dikonversi menjadi angka
    if (typeof number !== 'number' && isNaN(Number(number))) {
        return '';
    }

    // Konversi input menjadi angka jika diperlukan
    number = Number(number);

    // Gunakan toLocaleString untuk memformat angka sesuai locale Indonesia
    return 'Rp' + number.toLocaleString('id-ID', { minimumFractionDigits: 0 });
}