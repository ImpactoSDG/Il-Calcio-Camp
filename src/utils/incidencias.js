const normalizeText = (txt) => String(txt || '')
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toUpperCase()

export const getIncidenciaVisualMeta = (descripcion) => {
  const value = normalizeText(descripcion)

  if (value.includes('ROJA')) {
    return {
      key: 'roja',
      label: 'Tarjeta roja',
      icon: 'red-card',
      tone: 'danger',
    }
  }

  if (value.includes('AMARILLA')) {
    return {
      key: 'amarilla',
      label: 'Tarjeta amarilla',
      icon: 'yellow-card',
      tone: 'warning',
    }
  }

  if (value.includes('GOL')) {
    return {
      key: 'gol',
      label: 'Gol',
      icon: 'ball',
      tone: 'success',
    }
  }

  return {
    key: 'default',
    label: 'Incidencia',
    icon: 'default',
    tone: 'muted',
  }
}
