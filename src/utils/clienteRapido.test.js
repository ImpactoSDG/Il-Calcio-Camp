import { describe, it, expect } from 'vitest';
import { resolverClienteRapido } from './clienteRapido';

describe('resolverClienteRapido', () => {
  // Regresión del bug de foreign key: un cliente NUEVO trae id temporal 'temp-...'
  // y debe reconocerse como nuevo (no como edición) para que el backend lo cree.
  it('trata un cliente nuevo con id temporal como nuevo (esNuevo=true, sin isUpdate)', () => {
    const clienteNuevo = {
      id: 'temp-1751654321',
      nombre_cliente: 'Juan Pérez',
      isNew: true,
    };

    const { payload, esNuevo } = resolverClienteRapido(clienteNuevo);

    expect(esNuevo).toBe(true);
    expect(payload.isUpdate).toBeUndefined();
    expect(payload.isNew).toBe(true);
    expect(payload.id).toBe('temp-1751654321');
  });

  it('trata un cliente existente seleccionado como edición (esNuevo=false, isUpdate=true)', () => {
    const clienteExistente = {
      id: 42,
      nombre_cliente: 'María Gómez',
      isNew: false,
    };

    const { payload, esNuevo } = resolverClienteRapido(clienteExistente);

    expect(esNuevo).toBe(false);
    expect(payload.isUpdate).toBe(true);
    expect(payload.id).toBe(42);
  });

  it('trata un cliente editado (isUpdate) como edición', () => {
    const clienteEditado = {
      id: 7,
      nombre_cliente: 'Editado',
      isUpdate: true,
    };

    const { payload, esNuevo } = resolverClienteRapido(clienteEditado);

    expect(esNuevo).toBe(false);
    expect(payload.isUpdate).toBe(true);
  });

  it('sin flags (edición de cliente temporal) lo trata como edición, no como nuevo', () => {
    // Un cliente temporal editado llega con id 'temp-...' pero sin isNew:
    // no debe confundirse con un cliente nuevo.
    const clienteTemporalEditado = {
      id: 'temp-999',
      nombre_cliente: 'Temporal editado',
    };

    const { payload, esNuevo } = resolverClienteRapido(clienteTemporalEditado);

    expect(esNuevo).toBe(false);
    expect(payload.isUpdate).toBe(true);
  });
});
