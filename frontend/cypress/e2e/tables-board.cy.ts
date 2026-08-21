describe('Tablero de Mesas y Toma de Pedidos E2E', () => {
  beforeEach(() => {
    // Interceptar llamadas API para simular el Backend
    cy.intercept('GET', '/api/tables', [
      { id: 1, name: 'Mesa 1', status: 'free' },
      { id: 2, name: 'Mesa 2', status: 'occupied' }
    ]).as('getTables');

    cy.intercept('GET', '/api/products', [
      { id: 1, name: 'Café', price: 2.50, stock: 100 },
      { id: 2, name: 'Tostada', price: 3.00, stock: 50 }
    ]).as('getProducts');

    cy.intercept('POST', '/api/orders', {
      statusCode: 201,
      body: { id: 99, tableId: 1, status: 'open', total: 5.50 }
    }).as('createOrder');

    cy.visit('/');
  });

  it('debe cargar el tablero de mesas y permitir tomar comandas', () => {
    cy.wait('@getTables');
    cy.contains('Tablero de Mesas').should('be.visible');
    cy.contains('Mesa 1').should('be.visible');

    // Seleccionar mesa para tomar comandas
    cy.contains('Tomar pedido').first().click();

    // Validar panel de comanda
    cy.contains('Nuevo pedido - mesa 1').should('be.visible');
    cy.wait('@getProducts');

    // Introducir cantidad
    cy.get('input[type="number"]').first().clear().type('2');

    // Enviar pedido
    cy.contains('button', 'Enviar pedido').click();
    cy.wait('@createOrder');

    // Verificar panel reseteado
    cy.contains('Selecciona una mesa en el tablero').should('be.visible');
  });
});
