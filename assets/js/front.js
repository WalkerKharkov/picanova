jQuery( function($) {

    $( document ).ready( function() {

        $('.picanova_options > div').fadeOut();
        var checkout_form = jQuery( 'form.variations_form' );
        var $priceEl;
        var base_variation_price = 0;

        checkout_form.on( 'woocommerce_variation_has_changed', function() {
            var variationId = $("input.variation_id").val();

            if(variationId && envData.options[variationId]) {
                $(".additional_options").html("");
                var $options = $('<div class="additional_options"></div>')

                var options = envData.options[variationId];

                $.each( options, function( key, value ) {

                    var select = '<label for="add_option['+key+']">';
                    if(value.is_required) {
                        select += value.name+'*</label><select required name="add_option['+key+']">'
                    } else {
                        select += value.name+'</label><select name="add_option['+key+']">'
                    }

                    select += '<option value="">Choose an option</option>'
                    $.each( value.values, function( optionKey, optionValues ) {
                        select += '<option data-add_price="'+optionValues.price+'" value="'+optionValues.id+'">'+optionValues.name;
                        if(optionValues.price != 0) {
                            select += ' (+'+optionValues.price+' EUR)';
                        }

                        select += '</option>'

                    });
                    select += "</select>"

                    $options.append(select);
                });
                $(".variations").after($options)
            } else {
                $(".additional_options").html("");
            }
        });

        /**
         * TASK 1
         */
        $('body').on('woocommerce_variation_has_changed', function () {
            $priceEl = $(".woocommerce-variation-price .woocommerce-Price-amount.amount").children('bdi');
            base_variation_price = parseFloat($priceEl.text().replace(',', '.'));
        })

        /**
         * TASK 1
         */
        $( "body" ).on("change", ".additional_options select", function() {
            var $selectedOptions = $('.additional_options').find('option:selected');
            var additional_price = 0;

            $selectedOptions.each(function () {
                let $this = $(this);

                if ($this.data('add_price')){
                    additional_price += parseFloat($this.data('add_price'));
                }
            });

            var current_price = base_variation_price + additional_price;
            var $currencySymbol = $priceEl.children('span');
            $priceEl.text((current_price + '').replace('.', ',')).append($currencySymbol);
        });
    });
});