window.GENERAL = {
    order: {
        dataLayerPaySystem(step, orderItems) {
            var description = step.getAttribute('data-psa-name');
            var stepId = parseInt(step.getAttribute('data-step-id'));
            window.dataLayer = window.dataLayer || [];
            for (const itemId in orderItems) {
                dataLayer.push({
                    'ecommerce': {
                        'currencyCode': orderItems[itemId].currencyCode,
                        'checkout': {
                            'actionField': {'step': stepId, 'option': description},
                            'products': [{
                                'name': orderItems[itemId].name,
                                'id': orderItems[itemId].id,
                                'price': orderItems[itemId].price,
                                'brand': orderItems[itemId].brand,
                                'category': orderItems[itemId].category,
                                'variant': orderItems[itemId].variant,
                                'quantity': orderItems[itemId].quantity,
                            }
                            ]
                        }
                    },
                    'event': 'gtm-ee-event',
                    'gtm-ee-event-category': 'Enhanced Ecommerce',
                    'gtm-ee-event-action': 'Checkout Step ' + stepId,
                    'gtm-ee-event-non-interaction': 'False',
                })
            }
        },

        rRocketOrderAdd(order) {
            var productIds = order.productIds;
            var contentElements = order.content;
            var items = [];
            for (const productId of productIds) {
                items.push(
                    {
                        'id': contentElements[productId].id,
                        'qnt': contentElements[productId].qnt,
                        'price': contentElements[productId].price,
                    }
                );
            }
            (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
                try {
                    rrApi.setEmail(order.userEmail, {"stockId": order.stockId});
                    rrApi.order({
                        "transaction": order.transactionId,
                        "items": items
                    });
                } catch(e) {}
            })
        },

        dataLayerOrderAdd(order) {
            var productIds = order.productIds;
            var contentElements = order.content;
            var products = [];
            for (const productId of productIds) {
                products.push(
                    {
                        'id': contentElements[productId].id,
                        'name': contentElements[productId].name,
                        'price': contentElements[productId].price,
                        'quantity': contentElements[productId].quantity,
                        'brand': contentElements[productId].brand,
                        'category': contentElements[productId].category,
                        'coupon': contentElements[productId].coupon,
                        'variant': contentElements[productId].variant,
                    }
                );
            }

            window.dataLayer = window.dataLayer || [];
            dataLayer.push({
                'ecommerce': {
                    'currencyCode': order.currency,
                    'purchase': {
                        'actionField': {
                            'id': order.orderId,
                            'affiliation': 'Juicy Couture',
                            'revenue': order.revenue,
                            'tax': order.tax,
                            'shipping': order.shipping,
                            'coupon': ''
                        },
                        'products': products
                    }
                },
                'event': 'gtm-ee-event',
                'gtm-ee-event-category': 'Enhanced Ecommerce',
                'gtm-ee-event-action': 'Purchase',
                'gtm-ee-event-non-interaction': 'False',
            });
        }
    },

    catalog: {
        rrApiAddToBasket(element, storeName) {
            try {
                rrApi.addToBasket(element.getAttribute('data-sku-id'),{'stockId': storeName});
            } catch(e) {}
        },

        dataLayerClicks(item, items) {
            var itemId = item.getAttribute('data-product-id');
            window.dataLayer = window.dataLayer || [];
            dataLayer.push({
                'ecommerce': {
                    'currencyCode': items[itemId].currencyCode,
                    'click': {
                        'actionField': {'list': items[itemId].list},
                        'products': [{
                            'name': items[itemId].name,
                            'id': items[itemId].id,
                            'price': items[itemId].price,
                            'brand': items[itemId].brand,
                            'category': items[itemId].category,
                            'variant': items[itemId].variant,
                            'position': items[itemId].position
                        }]
                    }
                },
                'event': 'gtm-ee-event',
                'gtm-ee-event-category': 'Enhanced Ecommerce',
                'gtm-ee-event-action': 'Product Clicks',
                'gtm-ee-event-non-interaction': 'False',
            })
        },

        dataLayerDetails(itemId, elementItem) {
            window.dataLayer = window.dataLayer || [];
            dataLayer.push({
                'ecommerce': {
                    'currencyCode': elementItem[itemId].currencyCode,
                    'detail': {
                        'actionField': {'list': elementItem[itemId].list},
                        'products': [{
                            'name': elementItem[itemId].name,
                            'id': elementItem[itemId].id,
                            'price': elementItem[itemId].price,
                            'brand': elementItem[itemId].brand,
                            'category': elementItem[itemId].category,
                            'variant': elementItem[itemId].variant,
                        }]
                    }
                },
                'event': 'gtm-ee-event',
                'gtm-ee-event-category': 'Enhanced Ecommerce',
                'gtm-ee-event-action': 'Product Details',
                'gtm-ee-event-non-interaction': 'True',
            })
        },

        dataLayerAdd(itemId, elementItem) {
            window.dataLayer = window.dataLayer || [];
            dataLayer.push({
                'ecommerce': {
                    'currencyCode': elementItem[itemId].currencyCode,
                    'add': {
                        'products': [{
                            'name': elementItem[itemId].name,
                            'id': elementItem[itemId].id,
                            'price': elementItem[itemId].price,
                            'brand': elementItem[itemId].brand,
                            'category': elementItem[itemId].category,
                            'variant': elementItem[itemId].variant,
                            'quantity': 1,
                        }]
                    }
                },
                'event': 'gtm-ee-event',
                'gtm-ee-event-category': 'Enhanced Ecommerce',
                'gtm-ee-event-action': 'Adding a Product to a Shopping Cart',
                'gtm-ee-event-non-interaction': 'False',
            })
        },

        dataLayerRemove(item, elementItem) {
            var itemId = item.getAttribute('data-product-id');
            window.dataLayer = window.dataLayer || [];
            dataLayer.push({
                'ecommerce': {
                    'currencyCode': elementItem[itemId].currencyCode,
                    'remove': {
                        'products': [{
                            'name': elementItem[itemId].name,
                            'id': elementItem[itemId].id,
                            'price': elementItem[itemId].price,
                            'brand': elementItem[itemId].brand,
                            'category': elementItem[itemId].category,
                            'variant': elementItem[itemId].variant,
                            'quantity': 1,
                        }]
                    }
                },
                'event': 'gtm-ee-event',
                'gtm-ee-event-category': 'Enhanced Ecommerce',
                'gtm-ee-event-action': 'Removing a Product from a Shopping Cart',
                'gtm-ee-event-non-interaction': 'False',
            })
        },

        dataLayerCheckoutStep(step, basketItems) {
            var stepId = null;
            if (typeof step === 'number') {
                stepId = step;
            } else {
                stepId = parseInt(step.getAttribute('data-step-id'));
            }
            window.dataLayer = window.dataLayer || [];
            for (const itemId in basketItems) {
                dataLayer.push({
                    'ecommerce': {
                        'currencyCode': basketItems[itemId].currencyCode,
                        'checkout': {
                            'actionField': {'step': stepId},
                            'products': [{
                                'name': basketItems[itemId].name,
                                'id': basketItems[itemId].id,
                                'price': basketItems[itemId].price,
                                'brand': basketItems[itemId].brand,
                                'category': basketItems[itemId].category,
                                'variant': basketItems[itemId].variant,
                                'quantity': basketItems[itemId].quantity,
                            }
                            ]
                        }
                    },
                    'event': 'gtm-ee-event',
                    'gtm-ee-event-category': 'Enhanced Ecommerce',
                    'gtm-ee-event-action': 'Checkout Step ' + stepId,
                    'gtm-ee-event-non-interaction': 'False',
                })
            }
        },

        dataLayerImpressions(productIds, catalogItems) {
            window.dataLayer = window.dataLayer || [];

            var products = [];
            for (const productId of productIds) {
                products.push(
                    {
                        'name': catalogItems[productId].name,
                        'id': catalogItems[productId].id,
                        'price': catalogItems[productId].price,
                        'brand': catalogItems[productId].brand,
                        'category': catalogItems[productId].category,
                        'variant': catalogItems[productId].variant,
                        'list': catalogItems[productId].list,
                        'position': catalogItems[productId].position,
                    }
                );
            }

            dataLayer.push({
                'ecommerce': {
                    'currencyCode': catalogItems['currencyCode'].currencyCode,
                    'impressions': {
                        'products': products
                    }
                },
                'event': 'gtm-ee-event',
                'gtm-ee-event-category': 'Enhanced Ecommerce',
                'gtm-ee-event-action': 'Product Impressions',
                'gtm-ee-event-non-interaction': 'True',
            })
        },
    },
};
