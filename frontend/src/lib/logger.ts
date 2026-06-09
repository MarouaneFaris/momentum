import log from 'loglevel'

log.setDefaultLevel(import.meta.env.DEV ? log.levels.DEBUG : log.levels.WARN)

export default log
