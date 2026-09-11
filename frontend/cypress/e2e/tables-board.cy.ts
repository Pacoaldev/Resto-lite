describe('Tablero de Mesas y Toma de Pedidos E2E', () => {
  beforeEach(() => {
    cy.intercept('GET', '/api/tables', [
      { id: 1, name: 'Mesa 1', status: 'free' },
      { id: 2, name: 'Mesa 2', status: 'occupied' }
    ]).as('getTables');

    cy.intercept('GET', '/api/products', [
      { id: 1, name: 'Café', price: 2.50, stock: 100 },
      { id: 2, name: 'Tostada', price: 3.00, stock: 50 }
    ]).as('getProducts');

    cy.intercept('GET', '/api/orders*', []).as('getOrders');

    cy.intercept('POST', '/api/orders', {
      statusCode: 201,
      body: { id: 99, tableId: 1, status: 'open', total: 5.50 }
    }).as('createOrder');

    cy.intercept('POST', '/api/tables/*/request-bill', {
      statusCode: 200,
      body: {
        tableId: 2,
        orderIds: [1],
        items: [{ name: 'Café', price: 2.5, quantity: 1 }],
        subtotal: 2.5,
        taxLabel: 'IVA',
        taxRate: 0.21,
        taxAmount: 0.53,
        total: 3.03,
        currency: 'EUR',
        country: 'es',
      },
    }).as('requestBill');

    cy.intercept('POST', '/api/tables/*/settle', {
      statusCode: 200,
      body: {
        tableId: 2,
        orderIds: [1],
        payment: { provider: 'stub', status: 'approved', transactionId: 'pay_2_test' },
      },
    }).as('settle');

    cy.intercept('GET', '/api/establishment', {
      country: 'es',
      currency: 'EUR',
      name: 'España — Madrid',
      taxLabel: 'IVA',
      taxRate: 0.21,
      options: [
        { country: 'es', currency: 'EUR', name: 'España — Madrid' },
        { country: 'mx', currency: 'MXN', name: 'México — CDMX' },
      ],
    }).as('getEstablishment');

    cy.visit('/');
  });

  it('debe cargar el tablero de mesas y permitir tomar comandas', () => {
    cy.wait('@getTables');
    cy.contains('Tablero de Mesas').should('be.visible');
    cy.contains('Mesa 1').should('be.visible');

    cy.contains('button', 'Tomar pedido').first().click();

    cy.contains('Nuevo pedido - mesa 1').should('be.visible');
    cy.wait('@getProducts');

    cy.get('input[type="number"]').first().clear().type('2');

    cy.contains('button', 'Enviar pedido').click();
    cy.wait('@createOrder');

    cy.contains('Selecciona una mesa en el tablero').should('be.visible');
  });

  it('ciclo de mesa: pedir cuenta y cobrar con pasarela stub', () => {
    cy.wait('@getTables');
    cy.contains('button', 'Pedir cuenta').click();
    cy.wait('@requestBill');
    cy.contains('Cuenta — mesa 2').should('be.visible');
    cy.contains('button', 'Cobrar y liberar mesa').click();
    cy.wait('@settle').its('response.body.payment.provider').should('eq', 'stub');
  });

  it('cambia país fiscal a México y refleja IVA MX en la cuenta', () => {
    cy.wait('@getEstablishment');

    cy.intercept('PUT', '/api/establishment', {
      statusCode: 200,
      body: {
        country: 'mx',
        currency: 'MXN',
        name: 'México — CDMX',
        taxLabel: 'IVA',
        taxRate: 0.16,
        options: [
          { country: 'es', currency: 'EUR', name: 'España — Madrid' },
          { country: 'mx', currency: 'MXN', name: 'México — CDMX' },
        ],
      },
    }).as('putEstablishment');

    cy.intercept('POST', '/api/tables/*/request-bill', {
      statusCode: 200,
      body: {
        tableId: 2,
        orderIds: [1],
        items: [{ name: 'Café', price: 2.5, quantity: 1 }],
        subtotal: 2.5,
        taxLabel: 'IVA',
        taxRate: 0.16,
        taxAmount: 0.4,
        total: 2.9,
        currency: 'MXN',
        country: 'mx',
      },
    }).as('requestBillMx');

    cy.get('#country-select').select('mx');
    cy.wait('@putEstablishment');

    cy.contains('button', 'Pedir cuenta').click();
    cy.wait('@requestBillMx');
    cy.contains('Cuenta — mesa 2').should('be.visible');
    cy.contains('MXN').should('be.visible');
    cy.contains('16%').should('be.visible');
  });
});
