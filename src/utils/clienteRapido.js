// Determina si un cliente es nuevo o existente basándose en el formato de su id
// Los clientes nuevos creados rápidamente tienen un id con formato 'temp-{timestamp}'
export const resolverClienteRapido = (cliente) => {
  if (!cliente) {
    return { payload: null, isNew: false };
  }

  const isNew = cliente.isNew === true || (typeof cliente.id === 'string' && cliente.id.startsWith('temp-'));

  return {
    payload: cliente,
    isNew
  };
};
